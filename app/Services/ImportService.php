<?php

namespace App\Services;

use App\Models\CreditInvoice;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\FinishedProductBatch;
use App\Models\FinishedStockMovement;
use App\Models\ImportBatch;
use App\Models\Material;
use App\Models\MaterialBatch;
use App\Models\MaterialCategory;
use App\Models\MaterialStockMovement;
use App\Models\MeasurementUnit;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleType;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportService
{
    public const TYPES = [
        'product_variants' => ['Product variant update', ['sku', 'name', 'status']],
        'barcodes' => ['Barcode update', ['sku', 'barcode']],
        'prices' => ['Price update', ['sku', 'retail_price', 'wholesale_price', 'distributor_price']],
        'raw_materials' => ['Raw material import', ['name', 'category', 'base_unit_symbol', 'sku', 'reorder_level']],
        'suppliers' => ['Supplier import', ['name', 'phone', 'email', 'address', 'status']],
        'customers' => ['Customer import', ['name', 'customer_type', 'sale_type', 'phone', 'email', 'location', 'communication_consent']],
        'opening_stock' => ['Opening stock import', ['stock_type', 'sku_or_name', 'batch_number', 'quantity', 'unit_cost', 'expiry_date']],
        'open_credit_balances' => ['Open credit balance import', ['customer_name', 'amount', 'due_date']],
    ];

    public function __construct(private readonly AuditService $audit) {}

    public function template(string $type): string
    {
        return implode(',', self::TYPES[$type][1])."\n";
    }

    public function run(string $type, string $csv, bool $dryRun, User $actor): ImportBatch
    {
        return DB::transaction(function () use ($type, $csv, $dryRun, $actor): ImportBatch {
            $rows = $this->parse($csv);
            $batch = ImportBatch::query()->create([
                'batch_number' => 'IMP-'.now()->format('Ymd').'-'.str_pad((string) (ImportBatch::query()->count() + 1), 4, '0', STR_PAD_LEFT),
                'import_type' => $type,
                'status' => $dryRun ? 'dry_run' : 'applied',
                'dry_run' => $dryRun,
                'total_rows' => $rows->count(),
                'created_by' => $actor->id,
            ]);

            $valid = 0;
            $created = [];

            foreach ($rows as $index => $row) {
                $errors = $this->validateRow($type, $row);
                if ($errors !== []) {
                    $batch->errors()->create(['row_number' => $index + 2, 'message' => implode(' ', $errors), 'row_data' => $row]);

                    continue;
                }

                $valid++;
                if (! $dryRun) {
                    $result = $this->applyRow($type, $row, $actor);
                    if (is_array($result) && array_is_list($result)) {
                        $created = array_merge($created, $result);
                    } else {
                        $created[] = $result;
                    }
                }
            }

            $batch->update([
                'valid_rows' => $valid,
                'error_rows' => $batch->errors()->count(),
                'status' => $dryRun ? ($batch->errors()->exists() ? 'dry_run_errors' : 'dry_run_passed') : ($batch->errors()->exists() ? 'applied_with_errors' : 'applied'),
                'summary' => ['created_or_updated' => count(array_filter($created))],
                'rollback_metadata' => ['created' => array_values(array_filter($created))],
                'applied_at' => $dryRun ? null : now(),
            ]);

            $this->audit->record($dryRun ? 'import.dry_run' : 'import.applied', $batch, [], $batch->fresh('errors')->toArray(), actor: $actor);

            return $batch->fresh('errors');
        });
    }

    public function rollback(ImportBatch $batch, User $actor): ImportBatch
    {
        return DB::transaction(function () use ($batch, $actor): ImportBatch {
            if ($batch->import_type !== 'opening_stock') {
                $batch->update(['status' => 'rollback_not_supported']);

                return $batch->fresh();
            }

            foreach (($batch->rollback_metadata['created'] ?? []) as $item) {
                if (($item['model'] ?? null) && ($item['id'] ?? null) && class_exists($item['model'])) {
                    $item['model']::query()->whereKey($item['id'])->delete();
                }
            }

            $old = $batch->toArray();
            $batch->update(['status' => 'rolled_back']);
            $this->audit->record('import.rolled_back', $batch, $old, $batch->fresh()->toArray(), actor: $actor);

            return $batch->fresh();
        });
    }

    private function parse(string $csv): Collection
    {
        $lines = collect(preg_split('/\r\n|\r|\n/', trim($csv)))->filter(fn ($line) => trim($line) !== '')->values();
        $headers = str_getcsv((string) $lines->shift());

        return $lines->map(function (string $line) use ($headers): array {
            $values = str_getcsv($line);

            return collect($headers)->mapWithKeys(fn ($header, $i) => [trim($header) => trim((string) ($values[$i] ?? ''))])->all();
        });
    }

    private function validateRow(string $type, array $row): array
    {
        $errors = [];
        foreach (self::TYPES[$type][1] as $requiredHeader) {
            if (! array_key_exists($requiredHeader, $row)) {
                $errors[] = 'Missing column '.$requiredHeader.'.';
            }
        }

        $required = match ($type) {
            'product_variants', 'barcodes', 'prices' => ['sku'],
            'raw_materials' => ['name', 'category', 'base_unit_symbol'],
            'suppliers' => ['name'],
            'customers' => ['name', 'customer_type'],
            'opening_stock' => ['stock_type', 'sku_or_name', 'batch_number', 'quantity'],
            'open_credit_balances' => ['customer_name', 'amount', 'due_date'],
        };

        foreach ($required as $field) {
            if (blank($row[$field] ?? null)) {
                $errors[] = $field.' is required.';
            }
        }

        if (($type === 'barcodes') && filled($row['barcode'] ?? null) && ProductVariant::query()->where('barcode', $row['barcode'])->where('sku', '!=', $row['sku'])->exists()) {
            $errors[] = 'Barcode is already assigned to another product.';
        }

        if (in_array($type, ['product_variants', 'barcodes', 'prices'], true) && ! ProductVariant::query()->where('sku', $row['sku'])->exists()) {
            $errors[] = 'SKU does not exist.';
        }

        return $errors;
    }

    private function applyRow(string $type, array $row, User $actor): ?array
    {
        return match ($type) {
            'product_variants' => $this->updateVariant($row),
            'barcodes' => $this->updateVariant(['sku' => $row['sku'], 'barcode' => $row['barcode']]),
            'prices' => $this->updateVariant($row),
            'raw_materials' => $this->material($row),
            'suppliers' => $this->supplier($row),
            'customers' => $this->customer($row),
            'opening_stock' => $this->openingStock($row, $actor),
            'open_credit_balances' => $this->openingCredit($row, $actor),
        };
    }

    private function updateVariant(array $row): ?array
    {
        $variant = ProductVariant::query()->where('sku', $row['sku'])->first();
        $variant?->update(collect($row)->except(['sku'])->filter(fn ($value) => $value !== '')->all());

        return null;
    }

    private function material(array $row): ?array
    {
        $unit = MeasurementUnit::query()->firstOrCreate(['symbol' => $row['base_unit_symbol']], ['name' => $row['base_unit_symbol'], 'type' => 'unit']);
        $category = MaterialCategory::query()->firstOrCreate(['slug' => Str::slug($row['category'])], ['name' => $row['category'], 'type' => 'raw_material']);
        Material::query()->updateOrCreate(['name' => $row['name']], ['material_category_id' => $category->id, 'base_unit_id' => $unit->id, 'sku' => $row['sku'] ?: null, 'reorder_level' => $row['reorder_level'] ?: null, 'status' => 'active']);

        return null;
    }

    private function supplier(array $row): ?array
    {
        Supplier::query()->updateOrCreate(['name' => $row['name']], ['phone' => $row['phone'] ?: null, 'email' => $row['email'] ?: null, 'address' => $row['address'] ?: null, 'status' => $row['status'] ?: 'active']);

        return null;
    }

    private function customer(array $row): ?array
    {
        $type = CustomerType::query()->where('name', $row['customer_type'])->firstOrFail();
        $saleType = filled($row['sale_type'] ?? null) ? SaleType::query()->where('name', $row['sale_type'])->first() : null;
        Customer::query()->updateOrCreate(['name' => $row['name']], ['customer_type_id' => $type->id, 'default_sale_type_id' => $saleType?->id, 'phone' => $row['phone'] ?: null, 'email' => $row['email'] ?: null, 'location' => $row['location'] ?: null, 'status' => 'active', 'communication_consent' => in_array(strtolower($row['communication_consent'] ?? ''), ['1', 'yes', 'true'], true)]);

        return null;
    }

    private function openingStock(array $row, User $actor): array
    {
        if ($row['stock_type'] === 'raw') {
            $material = Material::query()->where('sku', $row['sku_or_name'])->orWhere('name', $row['sku_or_name'])->firstOrFail();
            $batch = MaterialBatch::query()->create(['material_id' => $material->id, 'lot_number' => $row['batch_number'], 'received_date' => now()->toDateString(), 'expiry_date' => $row['expiry_date'] ?: null, 'quantity_received' => $row['quantity'], 'quantity_available' => $row['quantity'], 'unit_cost' => $row['unit_cost'] ?: null]);
            $movement = MaterialStockMovement::query()->create(['material_id' => $material->id, 'material_batch_id' => $batch->id, 'unit_id' => $material->base_unit_id, 'movement_type' => 'opening_balance', 'quantity' => $row['quantity'], 'unit_cost' => $row['unit_cost'] ?: null, 'created_by' => $actor->id, 'occurred_at' => now(), 'notes' => 'Opening balance import.']);

            return [
                ['model' => MaterialStockMovement::class, 'id' => $movement->id],
                ['model' => MaterialBatch::class, 'id' => $batch->id],
            ];
        }

        $variant = ProductVariant::query()->where('sku', $row['sku_or_name'])->orWhere('name', $row['sku_or_name'])->firstOrFail();
        $batch = FinishedProductBatch::query()->create(['product_variant_id' => $variant->id, 'batch_number' => $row['batch_number'], 'production_date' => now()->toDateString(), 'expiry_date' => $row['expiry_date'] ?: null, 'quantity_received' => $row['quantity'], 'quantity_available' => $row['quantity'], 'unit_cost' => $row['unit_cost'] ?: null, 'status' => 'available']);
        $movement = FinishedStockMovement::query()->create(['product_variant_id' => $variant->id, 'finished_product_batch_id' => $batch->id, 'movement_type' => 'opening_balance', 'quantity' => $row['quantity'], 'unit_cost' => $row['unit_cost'] ?: null, 'created_by' => $actor->id, 'occurred_at' => now(), 'notes' => 'Opening balance import.']);

        return [
            ['model' => FinishedStockMovement::class, 'id' => $movement->id],
            ['model' => FinishedProductBatch::class, 'id' => $batch->id],
        ];
    }

    private function openingCredit(array $row, User $actor): ?array
    {
        $customer = Customer::query()->where('name', $row['customer_name'])->firstOrFail();
        $saleType = SaleType::query()->where('name', 'Retail')->firstOrFail();
        $sale = Sale::query()->create(['sale_number' => 'OPEN-CREDIT-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)), 'customer_id' => $customer->id, 'sale_type_id' => $saleType->id, 'created_by' => $actor->id, 'sale_date' => now(), 'subtotal' => $row['amount'], 'total' => $row['amount'], 'balance_due' => $row['amount'], 'status' => 'completed', 'payment_status' => 'unpaid', 'notes' => 'Opening credit balance import.']);
        CreditInvoice::query()->create(['invoice_number' => 'OPEN-CR-'.now()->format('Ymd').'-'.Str::upper(Str::random(5)), 'sale_id' => $sale->id, 'customer_id' => $customer->id, 'issue_date' => now()->toDateString(), 'due_date' => $row['due_date'], 'total' => $row['amount'], 'balance' => $row['amount'], 'status' => 'not_due']);
        $customer->increment('outstanding_balance', (float) $row['amount']);

        return ['model' => Sale::class, 'id' => $sale->id];
    }
}
