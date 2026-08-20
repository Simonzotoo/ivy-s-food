<x-layouts.app title="Record Expense">
    <section class="mb-5 rounded-md border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-xl font-bold text-ivy-navy">Record expense</h2>
        <p class="mt-1 text-sm text-slate-600">Enter what was spent, how it was paid and the amount. Receipt details can be added if available.</p>
    </section>

    <form method="POST" action="{{ route('expenses.store') }}" class="space-y-6 rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label class="form-label" for="expense_category_id">Category</label>
                <select class="form-input" id="expense_category_id" name="expense_category_id" required>
                    <option value="">Select category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('expense_category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="incurred_on">Date</label>
                <input class="form-input" id="incurred_on" name="incurred_on" type="date" value="{{ old('incurred_on', now()->toDateString()) }}" required>
            </div>
            <div>
                <label class="form-label" for="payment_method">Payment method</label>
                <select class="form-input" id="payment_method" name="payment_method" required>
                    @foreach (['Cash', 'MoMo', 'Bank', 'Other'] as $method)
                        <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ $method }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="amount">Amount (GHS)</label>
                <input class="form-input" id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" required>
            </div>
        </div>
        <div>
            <label class="form-label" for="description">Description</label>
            <textarea class="form-input" id="description" name="description" rows="4" required>{{ old('description') }}</textarea>
        </div>
        <details class="rounded-md border border-slate-200 bg-slate-50 p-4">
            <summary class="cursor-pointer text-sm font-bold text-ivy-navy">More details</summary>
            <h2 class="mt-4 text-base font-bold text-ivy-navy">Receipt reference</h2>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                <input class="form-input mt-0" name="receipt_original_name" value="{{ old('receipt_original_name') }}" placeholder="Receipt filename or reference">
                <input class="form-input mt-0" name="receipt_path" value="{{ old('receipt_path') }}" placeholder="Stored receipt path or reference">
            </div>
        </details>
        <div class="flex justify-end gap-3">
            <a href="{{ route('expenses.index') }}" class="btn-secondary">Cancel</a>
            <button class="btn-primary" type="submit" @disabled($categories->isEmpty())>Save for review</button>
        </div>
    </form>
</x-layouts.app>
