<x-layouts.app title="Record Expense">
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
        <details class="rounded-md border border-slate-200 p-4" {{ old('receipt_original_name') || old('receipt_path') ? 'open' : '' }}>
            <summary class="cursor-pointer text-sm font-semibold text-ivy-navy">More details (receipt reference)</summary>
            <p class="panel-help mt-2">If you have a paper or filed receipt, note where to find it.</p>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                <div>
                    <label class="form-label" for="receipt_original_name">Receipt name</label>
                    <input class="form-input" id="receipt_original_name" name="receipt_original_name" value="{{ old('receipt_original_name') }}" placeholder="e.g. Fuel receipt 21 Aug">
                </div>
                <div>
                    <label class="form-label" for="receipt_path">Filing reference</label>
                    <input class="form-input" id="receipt_path" name="receipt_path" value="{{ old('receipt_path') }}" placeholder="Where it's filed or stored">
                </div>
            </div>
        </details>
        <div class="flex justify-end gap-3">
            <a href="{{ route('expenses.index') }}" class="btn-secondary">Cancel</a>
            <button class="btn-primary" type="submit" @disabled($categories->isEmpty())>Save for review</button>
        </div>
    </form>
</x-layouts.app>
