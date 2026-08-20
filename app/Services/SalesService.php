<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\CreditInvoice;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\FinishedProductBatch;
use App\Models\FinishedStockMovement;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleType;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesService
{
    public function __construct(private readonly AuditService $audit) {}

    public function createSale(array $data, User $actor): Sale
    {
        return DB::transaction(function () use ($data, $actor): Sale {
            $saleType = SaleType::query()->findOrFail($data['sale_type_id']);
            $customer = filled($data['customer_id'] ?? null) ? Customer::query()->lockForUpdate()->findOrFail($data['customer_id']) : null;
            $payments = collect($data['payments'] ?? [])->filter(fn (array $payment) => BigDecimal::of((string) ($payment['amount'] ?? 0))->isGreaterThanOrEqualTo(BigDecimal::zero()));
            $hasCredit = $payments->contains(fn (array $payment) => ($payment['payment_method'] ?? null) === 'Credit');

            if ($hasCredit && ! $customer) {
                throw ValidationException::withMessages(['customer_id' => 'Credit sales require a customer.']);
            }

            $sale = Sale::query()->create([
                'sale_number' => $this->nextSaleNumber(),
                'customer_id' => $customer?->id,
                'sale_type_id' => $saleType->id,
                'created_by' => $actor->id,
                'sale_date' => $data['sale_date'] ?? now(),
                'subtotal' => '0.00',
                'discount_total' => '0.00',
                'total' => '0.00',
                'amount_paid' => '0.00',
                'balance_due' => '0.00',
                'status' => 'completed',
                'payment_status' => 'unpaid',
                'notes' => $data['notes'] ?? null,
            ]);

            $subtotal = BigDecimal::zero();
            $discountTotal = BigDecimal::zero();

            foreach ($data['items'] as $item) {
                $variant = ProductVariant::query()->findOrFail($item['product_variant_id']);
                $quantity = BigDecimal::of((string) $item['quantity'])->toScale(3, RoundingMode::HalfUp);
                $unitPrice = $this->priceFor($variant, $saleType);
                $discount = BigDecimal::of((string) ($item['discount_amount'] ?? 0))->toScale(2, RoundingMode::HalfUp);
                $gross = $quantity->multipliedBy($unitPrice)->toScale(2, RoundingMode::HalfUp);

                if ($discount->isGreaterThan($gross)) {
                    throw ValidationException::withMessages(['discount_amount' => 'Discount cannot exceed line total.']);
                }

                $lineTotal = $gross->minus($discount)->toScale(2, RoundingMode::HalfUp);
                $subtotal = $subtotal->plus($gross);
                $discountTotal = $discountTotal->plus($discount);

                $saleItem = $sale->items()->create([
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discount,
                    'line_total' => $lineTotal,
                ]);

                $this->deductFinishedStock($saleItem, $quantity, $actor);
            }

            $total = $subtotal->minus($discountTotal)->toScale(2, RoundingMode::HalfUp);
            $paid = BigDecimal::zero();
            foreach ($payments as $payment) {
                if (($payment['payment_method'] ?? null) === 'Credit') {
                    continue;
                }

                $amount = BigDecimal::of((string) ($payment['amount'] ?? 0))->toScale(2, RoundingMode::HalfUp);
                if ($amount->isZero()) {
                    continue;
                }

                $paid = $paid->plus($amount);
                $sale->payments()->create([
                    'payment_method' => $payment['payment_method'],
                    'amount' => $amount,
                    'momo_network' => $payment['momo_network'] ?? null,
                    'transaction_reference' => $payment['transaction_reference'] ?? null,
                    'received_at' => now(),
                    'received_by' => $actor->id,
                ]);
            }

            if ($paid->isGreaterThan($total)) {
                throw ValidationException::withMessages(['payments' => 'Payments cannot exceed sale total.']);
            }

            $balance = $total->minus($paid)->toScale(2, RoundingMode::HalfUp);
            $sale->update([
                'subtotal' => $subtotal->toScale(2, RoundingMode::HalfUp),
                'discount_total' => $discountTotal->toScale(2, RoundingMode::HalfUp),
                'total' => $total,
                'amount_paid' => $paid->toScale(2, RoundingMode::HalfUp),
                'balance_due' => $balance,
                'payment_status' => $balance->isZero() ? 'paid' : ($paid->isZero() ? 'unpaid' : 'partial'),
            ]);

            if ($balance->isGreaterThan(BigDecimal::zero())) {
                if (! $hasCredit) {
                    throw ValidationException::withMessages(['payments' => 'Unpaid balances must be recorded as Credit.']);
                }

                $this->createCreditInvoice($sale->fresh(), $customer, $balance);
                $customer->update([
                    'outstanding_balance' => BigDecimal::of((string) $customer->outstanding_balance)->plus($balance)->toScale(2, RoundingMode::HalfUp),
                ]);
            }

            $this->createDeliveryNote($sale->fresh(), $customer);
            $this->audit->record('sale.completed', $sale, [], $sale->fresh(['items.batches', 'payments', 'creditInvoice'])->toArray(), actor: $actor);

            return $sale->fresh(['customer', 'saleType', 'items.productVariant', 'payments', 'creditInvoice', 'deliveryNote']);
        });
    }

    public function reverseSale(Sale $sale, array $data, User $actor): Sale
    {
        return DB::transaction(function () use ($sale, $data, $actor): Sale {
            if ($sale->status !== 'completed') {
                throw ValidationException::withMessages(['sale' => 'Only completed sales can be reversed.']);
            }
            if ($sale->returns()->exists()) {
                throw ValidationException::withMessages(['sale' => 'Sales with returns cannot be fully reversed.']);
            }

            foreach ($sale->items()->with('batches.finishedProductBatch')->get() as $item) {
                foreach ($item->batches as $batchLine) {
                    $finishedBatch = $batchLine->finishedProductBatch;
                    $available = BigDecimal::of((string) $finishedBatch->quantity_available);
                    $finishedBatch->update([
                        'quantity_available' => $available->plus((string) $batchLine->quantity)->toScale(3, RoundingMode::HalfUp),
                    ]);
                    $this->finishedMovement($finishedBatch, 'sale_reversal', BigDecimal::of((string) $batchLine->quantity), $sale, $actor, $data['reason']);
                }
            }

            if ($sale->customer && BigDecimal::of((string) $sale->balance_due)->isGreaterThan(BigDecimal::zero())) {
                $sale->customer->update([
                    'outstanding_balance' => BigDecimal::of((string) $sale->customer->outstanding_balance)->minus((string) $sale->balance_due)->toScale(2, RoundingMode::HalfUp),
                ]);
            }

            $old = $sale->only(['status']);
            $sale->update([
                'status' => 'cancelled',
                'reversal_reason' => $data['reason'],
                'reversed_by' => $actor->id,
                'reversed_at' => now(),
            ]);
            $sale->creditInvoice?->update(['status' => 'cancelled']);
            $this->audit->record('sale.reversed', $sale, $old, $sale->fresh()->toArray(), actor: $actor);

            return $sale->fresh(['items.batches', 'creditInvoice']);
        });
    }

    public function returnItems(Sale $sale, array $data, User $actor): SaleReturn
    {
        return DB::transaction(function () use ($sale, $data, $actor): SaleReturn {
            if ($sale->status !== 'completed') {
                throw ValidationException::withMessages(['sale' => 'Only completed sales can receive returns.']);
            }

            $return = SaleReturn::query()->create([
                'return_number' => $this->nextReturnNumber(),
                'sale_id' => $sale->id,
                'return_date' => $data['return_date'],
                'reason' => $data['reason'],
                'status' => 'completed',
                'created_by' => $actor->id,
            ]);
            $total = BigDecimal::zero();

            foreach ($data['items'] as $returnItem) {
                $item = $sale->items()->with('batches.finishedProductBatch')->findOrFail($returnItem['sale_item_id']);
                $quantity = BigDecimal::of((string) $returnItem['quantity'])->toScale(3, RoundingMode::HalfUp);
                if ($quantity->isGreaterThan(BigDecimal::of((string) $item->quantity))) {
                    throw ValidationException::withMessages(['quantity' => 'Return quantity cannot exceed sold quantity.']);
                }

                $refund = $quantity->multipliedBy((string) $item->unit_price)->toScale(2, RoundingMode::HalfUp);
                $return->items()->create([
                    'sale_item_id' => $item->id,
                    'quantity' => $quantity,
                    'refund_amount' => $refund,
                ]);
                $total = $total->plus($refund);

                $remaining = $quantity;
                foreach ($item->batches as $batchLine) {
                    if ($remaining->isZero()) {
                        break;
                    }
                    $take = $remaining->isLessThanOrEqualTo(BigDecimal::of((string) $batchLine->quantity)) ? $remaining : BigDecimal::of((string) $batchLine->quantity);
                    $finishedBatch = $batchLine->finishedProductBatch;
                    $finishedBatch->update([
                        'quantity_available' => BigDecimal::of((string) $finishedBatch->quantity_available)->plus($take)->toScale(3, RoundingMode::HalfUp),
                    ]);
                    $this->finishedMovement($finishedBatch, 'sale_return', $take, $return, $actor, $data['reason']);
                    $remaining = $remaining->minus($take)->toScale(3, RoundingMode::HalfUp);
                }
            }

            $return->update(['total' => $total->toScale(2, RoundingMode::HalfUp)]);
            $this->audit->record('sale.returned', $return, [], $return->fresh('items')->toArray(), actor: $actor);

            return $return->fresh('items');
        });
    }

    private function deductFinishedStock(SaleItem $saleItem, BigDecimal $quantity, User $actor): void
    {
        $remaining = $quantity;
        $batches = FinishedProductBatch::query()
            ->where('product_variant_id', $saleItem->product_variant_id)
            ->where('status', 'available')
            ->where('quantity_available', '>', 0)
            ->orderByRaw('expiry_date IS NULL, expiry_date asc')
            ->orderBy('production_date')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remaining->isZero()) {
                break;
            }

            $available = BigDecimal::of((string) $batch->quantity_available);
            $take = $remaining->isLessThanOrEqualTo($available) ? $remaining : $available;
            $batch->update(['quantity_available' => $available->minus($take)->toScale(3, RoundingMode::HalfUp)]);
            $saleItem->batches()->create([
                'finished_product_batch_id' => $batch->id,
                'quantity' => $take,
                'unit_cost' => $batch->unit_cost,
            ]);
            $this->finishedMovement($batch, 'sale_deduction', $take->negated(), $saleItem->sale, $actor, 'Sale '.$saleItem->sale->sale_number);
            $remaining = $remaining->minus($take)->toScale(3, RoundingMode::HalfUp);
        }

        if ($remaining->isGreaterThan(BigDecimal::zero())) {
            throw ValidationException::withMessages(['items' => 'Insufficient finished stock for '.$saleItem->productVariant->name.'.']);
        }
    }

    private function priceFor(ProductVariant $variant, SaleType $saleType): BigDecimal
    {
        $field = match ($saleType->slug) {
            'retail' => 'retail_price',
            'wholesale' => 'wholesale_price',
            'distributor' => 'distributor_price',
            default => null,
        };

        if (! $field || $variant->status !== 'active' || $variant->{$field} === null) {
            throw ValidationException::withMessages(['items' => 'Selected product is not active or lacks '.$saleType->name.' pricing.']);
        }

        return BigDecimal::of((string) $variant->{$field})->toScale(2, RoundingMode::HalfUp);
    }

    private function createCreditInvoice(Sale $sale, Customer $customer, BigDecimal $balance): void
    {
        $issue = now()->toDateString();
        $days = CompanySetting::query()->first()?->default_credit_period_days ?? 30;
        CreditInvoice::query()->create([
            'invoice_number' => 'INV-'.now()->format('Ymd').'-'.str_pad((string) (CreditInvoice::query()->count() + 1), 4, '0', STR_PAD_LEFT),
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'issue_date' => $issue,
            'due_date' => now()->addDays($days)->toDateString(),
            'total' => $sale->total,
            'balance' => $balance,
            'status' => 'not_due',
        ]);
    }

    private function createDeliveryNote(Sale $sale, ?Customer $customer): void
    {
        DeliveryNote::query()->create([
            'note_number' => 'DN-'.now()->format('Ymd').'-'.str_pad((string) (DeliveryNote::query()->count() + 1), 4, '0', STR_PAD_LEFT),
            'sale_id' => $sale->id,
            'delivery_address' => $customer?->address,
            'status' => 'issued',
        ]);
    }

    private function finishedMovement(FinishedProductBatch $batch, string $type, BigDecimal $quantity, mixed $reference, User $actor, string $notes): void
    {
        FinishedStockMovement::query()->create([
            'product_variant_id' => $batch->product_variant_id,
            'finished_product_batch_id' => $batch->id,
            'movement_type' => $type,
            'quantity' => $quantity->toScale(3, RoundingMode::HalfUp),
            'unit_cost' => $batch->unit_cost,
            'reference_type' => $reference ? $reference::class : null,
            'reference_id' => $reference?->id,
            'created_by' => $actor->id,
            'occurred_at' => now(),
            'notes' => $notes,
        ]);
    }

    private function nextSaleNumber(): string
    {
        return 'SALE-'.now()->format('Ymd').'-'.str_pad((string) (Sale::query()->count() + 1), 4, '0', STR_PAD_LEFT);
    }

    private function nextReturnNumber(): string
    {
        return 'SRET-'.now()->format('Ymd').'-'.str_pad((string) (SaleReturn::query()->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
