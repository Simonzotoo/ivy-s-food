<?php

namespace App\Services;

use App\Contracts\NotificationChannel;
use App\Models\CreditInvoice;
use App\Models\CreditReminder;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\ReminderTemplate;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreditService
{
    public function __construct(private readonly AuditService $audit, private readonly NotificationChannel $channel) {}

    public function recordPayment(Customer $customer, array $data, User $actor): CustomerPayment
    {
        return DB::transaction(function () use ($customer, $data, $actor): CustomerPayment {
            $amount = BigDecimal::of((string) $data['amount'])->toScale(2, RoundingMode::HalfUp);
            $payment = CustomerPayment::query()->create([
                'payment_number' => $this->nextPaymentNumber(),
                'customer_id' => $customer->id,
                'payment_method' => $data['payment_method'],
                'amount' => $amount,
                'unallocated_amount' => $amount,
                'momo_network' => $data['momo_network'] ?? null,
                'transaction_reference' => $data['transaction_reference'] ?? null,
                'received_at' => $data['received_at'] ?? now(),
                'received_by' => $actor->id,
            ]);

            $remaining = $amount;
            $invoices = CreditInvoice::query()
                ->where('customer_id', $customer->id)
                ->where('balance', '>', 0)
                ->orderBy('due_date')
                ->lockForUpdate()
                ->get();

            foreach ($invoices as $invoice) {
                if ($remaining->isZero()) {
                    break;
                }

                $balance = BigDecimal::of((string) $invoice->balance);
                $allocation = $remaining->isLessThanOrEqualTo($balance) ? $remaining : $balance;
                $payment->allocations()->create([
                    'credit_invoice_id' => $invoice->id,
                    'amount' => $allocation,
                ]);

                $newBalance = $balance->minus($allocation)->toScale(2, RoundingMode::HalfUp);
                $invoice->update([
                    'balance' => $newBalance,
                    'status' => $newBalance->isZero() ? 'paid' : 'partially_paid',
                ]);
                $remaining = $remaining->minus($allocation)->toScale(2, RoundingMode::HalfUp);
            }

            $payment->update(['unallocated_amount' => $remaining]);
            $customer->update([
                'outstanding_balance' => BigDecimal::of((string) $customer->outstanding_balance)->minus($amount->minus($remaining))->toScale(2, RoundingMode::HalfUp),
            ]);

            $this->refreshStatuses($customer);
            $this->audit->record('credit.payment_recorded', $payment, [], $payment->fresh('allocations')->toArray(), actor: $actor);

            return $payment->fresh(['customer', 'allocations.invoice']);
        });
    }

    public function refreshStatuses(?Customer $customer = null): void
    {
        CreditInvoice::query()
            ->when($customer, fn ($query) => $query->where('customer_id', $customer->id))
            ->where('balance', '>', 0)
            ->get()
            ->each(function (CreditInvoice $invoice): void {
                $today = now()->toDateString();
                $status = match (true) {
                    $invoice->due_date->isPast() && ! $invoice->due_date->isSameDay(now()) => 'overdue',
                    $invoice->due_date->isSameDay(now()) => 'due_today',
                    $invoice->due_date->lte(now()->addDays(7)) => 'due_soon',
                    default => 'not_due',
                };
                if ($invoice->status !== 'partially_paid') {
                    $invoice->update(['status' => $status]);
                }
            });
    }

    public function sendReminder(CreditInvoice $invoice, ?ReminderTemplate $template, User $actor): CreditReminder
    {
        return DB::transaction(function () use ($invoice, $template, $actor): CreditReminder {
            if (BigDecimal::of((string) $invoice->balance)->isZero()) {
                throw ValidationException::withMessages(['invoice' => 'Paid invoices do not need reminders.']);
            }

            $message = $template?->body ?: 'Reminder: invoice '.$invoice->invoice_number.' has an outstanding balance of GHS '.$invoice->balance.'.';
            $reminder = CreditReminder::query()->create([
                'credit_invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'reminder_template_id' => $template?->id,
                'channel' => $template?->channel ?? 'local',
                'status' => 'logged',
                'message' => $message,
                'sent_at' => now(),
                'created_by' => $actor->id,
            ]);

            $payload = $this->channel->send($reminder);
            $reminder->update(['provider_payload' => $payload]);
            $this->audit->record('credit.reminder_logged', $reminder, [], $reminder->fresh()->toArray(), actor: $actor);

            return $reminder->fresh();
        });
    }

    private function nextPaymentNumber(): string
    {
        return 'CPAY-'.now()->format('Ymd').'-'.str_pad((string) (CustomerPayment::query()->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
