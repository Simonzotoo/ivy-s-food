<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    public function __construct(private readonly AuditService $audit) {}

    public function create(array $data, User $actor): Customer
    {
        return DB::transaction(function () use ($data, $actor): Customer {
            $data = $this->prepare($data);
            $this->guardDuplicates($data);

            $customer = Customer::query()->create($data);
            $this->audit->record('customer.created', $customer, [], $customer->toArray(), actor: $actor);

            return $customer->fresh(['type', 'defaultSaleType']);
        });
    }

    public function update(Customer $customer, array $data, User $actor): Customer
    {
        return DB::transaction(function () use ($customer, $data, $actor): Customer {
            $data = $this->prepare($data);
            $this->guardDuplicates($data, $customer);

            $old = $customer->only(array_keys($data));
            $customer->update($data);
            $this->audit->record('customer.updated', $customer, $old, $customer->fresh()->only(array_keys($data)), actor: $actor);

            return $customer->fresh(['type', 'defaultSaleType']);
        });
    }

    public function logCommunication(Customer $customer, array $data, User $actor): void
    {
        DB::transaction(function () use ($customer, $data, $actor): void {
            $communication = $customer->communications()->create([
                'channel' => $data['channel'],
                'direction' => $data['direction'],
                'subject' => $data['subject'] ?? null,
                'message' => $data['message'] ?? null,
                'status' => 'logged',
                'created_by' => $actor->id,
                'occurred_at' => $data['occurred_at'] ?? now(),
            ]);

            $this->audit->record('customer.communication_logged', $communication, [], $communication->toArray(), actor: $actor);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function prepare(array $data): array
    {
        $data['normalized_phone'] = PhoneNormalizer::ghana($data['phone'] ?? null);
        $data['communication_consent'] = (bool) ($data['communication_consent'] ?? false);
        $data['outstanding_balance'] = $data['outstanding_balance'] ?? '0.00';

        return $data;
    }

    private function guardDuplicates(array $data, ?Customer $ignore = null): void
    {
        $query = Customer::query();
        if ($ignore) {
            $query->whereKeyNot($ignore->id);
        }

        if (filled($data['normalized_phone'] ?? null) && (clone $query)->where('normalized_phone', $data['normalized_phone'])->exists()) {
            throw ValidationException::withMessages(['phone' => 'Another customer already uses this phone number.']);
        }

        if (filled($data['email'] ?? null) && (clone $query)->where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages(['email' => 'Another customer already uses this email address.']);
        }
    }
}
