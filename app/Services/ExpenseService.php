<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function __construct(private readonly AuditService $audit) {}

    public function createCategory(array $data, User $actor): ExpenseCategory
    {
        return DB::transaction(function () use ($data, $actor): ExpenseCategory {
            $category = ExpenseCategory::query()->create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->audit->record('expense.category_created', $category, [], $category->toArray(), actor: $actor);

            return $category;
        });
    }

    public function createExpense(array $data, User $actor): Expense
    {
        return DB::transaction(function () use ($data, $actor): Expense {
            $category = ExpenseCategory::query()->whereKey($data['expense_category_id'])->lockForUpdate()->firstOrFail();
            if (! $category->is_active) {
                throw ValidationException::withMessages(['expense_category_id' => 'Inactive categories cannot be used for new expenses.']);
            }

            $amount = BigDecimal::of((string) $data['amount'])->toScale(2, RoundingMode::HalfUp);
            $expense = Expense::query()->create([
                'expense_number' => $this->nextExpenseNumber(),
                'expense_category_id' => $category->id,
                'incurred_on' => $data['incurred_on'],
                'payment_method' => $data['payment_method'],
                'amount' => $amount,
                'description' => $data['description'],
                'receipt_path' => $data['receipt_path'] ?? null,
                'receipt_original_name' => $data['receipt_original_name'] ?? null,
                'status' => 'pending',
                'created_by' => $actor->id,
            ]);

            $this->audit->record('expense.created', $expense, [], $expense->fresh(['category'])->toArray(), actor: $actor);

            return $expense->fresh(['category', 'creator']);
        });
    }

    public function approve(Expense $expense, User $actor): Expense
    {
        return DB::transaction(function () use ($expense, $actor): Expense {
            $expense = Expense::query()->lockForUpdate()->findOrFail($expense->id);
            if ($expense->status !== 'pending') {
                throw ValidationException::withMessages(['expense' => 'Only pending expenses can be approved.']);
            }

            $old = $expense->toArray();
            $expense->update([
                'status' => 'approved',
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

            $this->audit->record('expense.approved', $expense, $old, $expense->fresh()->toArray(), actor: $actor);

            return $expense->fresh(['category', 'creator', 'approver']);
        });
    }

    public function reject(Expense $expense, string $reason, User $actor): Expense
    {
        return DB::transaction(function () use ($expense, $reason, $actor): Expense {
            $expense = Expense::query()->lockForUpdate()->findOrFail($expense->id);
            if ($expense->status !== 'pending') {
                throw ValidationException::withMessages(['expense' => 'Only pending expenses can be rejected.']);
            }

            $old = $expense->toArray();
            $expense->update([
                'status' => 'rejected',
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $this->audit->record('expense.rejected', $expense, $old, $expense->fresh()->toArray(), actor: $actor);

            return $expense->fresh(['category', 'creator', 'approver']);
        });
    }

    private function nextExpenseNumber(): string
    {
        return 'EXP-'.now()->format('Ymd').'-'.str_pad((string) (Expense::query()->count() + 1), 4, '0', STR_PAD_LEFT);
    }
}
