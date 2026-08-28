<?php

namespace App\Services;

use App\Contracts\BroadcastDeliveryChannel;
use App\Models\Broadcast;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BroadcastService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly BroadcastDeliveryChannel $delivery,
    ) {}

    public function previewRecipients(array $filters): Collection
    {
        return $this->recipientQuery($filters)->orderBy('name')->limit(100)->get();
    }

    public function createBroadcast(array $data, User $actor): Broadcast
    {
        return DB::transaction(function () use ($data, $actor): Broadcast {
            $filters = $this->normalizeFilters($data);
            $recipients = $this->recipientQuery($filters)->lockForUpdate()->get();

            if ($recipients->isEmpty()) {
                throw ValidationException::withMessages(['recipients' => 'No consenting recipients match these filters.']);
            }

            $broadcast = Broadcast::query()->create([
                'broadcast_number' => $this->nextBroadcastNumber(),
                'broadcast_template_id' => $data['broadcast_template_id'] ?? null,
                'name' => $data['name'],
                'channel' => $data['channel'],
                'subject' => $data['subject'] ?? null,
                'body' => $data['body'],
                'status' => filled($data['scheduled_for'] ?? null) ? 'scheduled' : 'draft',
                'filters' => $filters,
                'scheduled_for' => $data['scheduled_for'] ?? null,
                'total_recipients' => $recipients->count(),
                'created_by' => $actor->id,
            ]);

            foreach ($recipients as $customer) {
                $broadcast->recipients()->create(['customer_id' => $customer->id]);
            }

            $this->audit->record('broadcast.created', $broadcast, [], $broadcast->fresh('recipients')->toArray(), actor: $actor);

            if (! filled($data['scheduled_for'] ?? null)) {
                $this->send($broadcast->fresh('recipients.customer'), $actor);
            }

            return $broadcast->fresh(['recipients.customer', 'creator']);
        });
    }

    public function send(Broadcast $broadcast, ?User $actor = null): Broadcast
    {
        return DB::transaction(function () use ($broadcast, $actor): Broadcast {
            $broadcast = Broadcast::query()->with('recipients.customer')->lockForUpdate()->findOrFail($broadcast->id);

            if (! in_array($broadcast->status, ['draft', 'scheduled'], true)) {
                throw ValidationException::withMessages(['broadcast' => 'Only draft or scheduled broadcasts can be sent.']);
            }

            $sent = 0;
            $skipped = 0;
            $failed = 0;

            foreach ($broadcast->recipients as $recipient) {
                $customer = $recipient->customer;
                if (! $customer || $customer->status !== 'active' || ! $customer->communication_consent || $customer->communication_opted_out_at) {
                    $recipient->update([
                        'status' => 'skipped',
                        'skip_reason' => 'Customer is inactive, lacks consent or has opted out.',
                    ]);
                    $skipped++;

                    continue;
                }

                $payload = $this->delivery->send($recipient);
                $succeeded = (bool) ($payload['success'] ?? false);
                $recipient->update([
                    'status' => $succeeded ? 'sent' : 'failed',
                    'skip_reason' => $succeeded ? null : ($payload['error'] ?? 'Delivery failed.'),
                    'provider_payload' => $payload,
                    'sent_at' => now(),
                ]);
                $customer->communications()->create([
                    'channel' => $broadcast->channel,
                    'direction' => 'outbound',
                    'subject' => $broadcast->subject,
                    'message' => $broadcast->body,
                    'status' => $succeeded ? 'sent' : 'failed',
                    'created_by' => $actor?->id,
                    'occurred_at' => now(),
                ]);

                if ($succeeded) {
                    $sent++;
                } else {
                    $failed++;
                }
            }

            $old = $broadcast->toArray();
            $broadcast->update([
                'status' => 'sent',
                'sent_at' => now(),
                'total_logged' => $sent,
                'total_skipped' => $skipped,
                'total_failed' => $failed,
            ]);

            $this->audit->record('broadcast.sent', $broadcast, $old, $broadcast->fresh('recipients')->toArray(), actor: $actor);

            return $broadcast->fresh(['recipients.customer', 'creator']);
        });
    }

    public function runScheduled(?User $actor = null): int
    {
        $count = 0;
        Broadcast::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->get()
            ->each(function (Broadcast $broadcast) use ($actor, &$count): void {
                $this->send($broadcast, $actor);
                $count++;
            });

        return $count;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function recipientQuery(array $filters): Builder
    {
        return Customer::query()
            ->where('status', 'active')
            ->where('communication_consent', true)
            ->whereNull('communication_opted_out_at')
            ->when(filled($filters['customer_type_id'] ?? null), fn ($query) => $query->where('customer_type_id', $filters['customer_type_id']))
            ->when(filled($filters['sale_type_id'] ?? null), fn ($query) => $query->where('default_sale_type_id', $filters['sale_type_id']))
            ->when(filled($filters['location'] ?? null), fn ($query) => $query->where('location', 'like', '%'.$filters['location'].'%'))
            ->when(($filters['purchase_history'] ?? 'any') === 'has_purchases', fn ($query) => $query->whereHas('sales'))
            ->when(($filters['purchase_history'] ?? 'any') === 'no_purchases', fn ($query) => $query->whereDoesntHave('sales'));
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $data): array
    {
        return [
            'customer_type_id' => $data['customer_type_id'] ?? null,
            'sale_type_id' => $data['sale_type_id'] ?? null,
            'location' => $data['location'] ?? null,
            'purchase_history' => $data['purchase_history'] ?? 'any',
        ];
    }

    private function nextBroadcastNumber(): string
    {
        return 'BCAST-'.now()->format('Ymd').'-'.str_pad((string) (Broadcast::query()->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
