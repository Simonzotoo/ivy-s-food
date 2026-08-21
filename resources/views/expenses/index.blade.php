<x-layouts.app title="Expenses">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="grid gap-3 rounded-md border border-slate-200 bg-white p-3 shadow-sm sm:grid-cols-[150px_220px_auto]">
            <select class="form-input mt-0" name="status">
                <option value="">All statuses</option>
                @foreach (['pending', 'approved', 'rejected'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <select class="form-input mt-0" name="category_id">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <button class="btn-secondary" type="submit">Filter</button>
        </form>
        <div class="flex flex-wrap gap-2">
            @can('expenses.manage_categories')
                <a href="{{ route('expenses.categories') }}" class="btn-secondary">Categories</a>
            @endcan
            @can('expenses.reports')
                <a href="{{ route('expenses.report') }}" class="btn-secondary">Report</a>
            @endcan
            @can('expenses.create')
                <a href="{{ route('expenses.create') }}" class="btn-primary">Record expense</a>
            @endcan
        </div>
    </div>

    <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Expense</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Method</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($expenses as $expense)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('expenses.show', $expense) }}" class="font-semibold text-ivy-navy hover:text-ivy-red">{{ $expense->expense_number }}</a>
                                <p class="max-w-md truncate text-xs text-slate-500">{{ $expense->description }}</p>
                            </td>
                            <td class="px-4 py-3">{{ $expense->category->name }}</td>
                            <td class="px-4 py-3">{{ $expense->incurred_on->format('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $expense->payment_method }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-ivy-navy">GHS {{ $expense->amount }}</td>
                            <td class="px-4 py-3"><span class="badge {{ $expense->status === 'approved' ? 'badge-success' : ($expense->status === 'rejected' ? 'badge-danger' : 'badge-warning') }}">{{ ucfirst($expense->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-slate-500">No expenses recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="divide-y divide-slate-100 md:hidden">
            @forelse ($expenses as $expense)
                <article class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <a href="{{ route('expenses.show', $expense) }}" class="font-semibold text-ivy-navy">{{ $expense->expense_number }}</a>
                        <p class="font-bold text-ivy-navy">GHS {{ $expense->amount }}</p>
                    </div>
                    <p class="mt-1 text-sm text-slate-600">{{ $expense->category->name }} · {{ $expense->payment_method }}</p>
                    <span class="badge {{ $expense->status === 'approved' ? 'badge-success' : ($expense->status === 'rejected' ? 'badge-danger' : 'badge-warning') }}">{{ ucfirst($expense->status) }}</span>
                    <p class="mt-1 text-xs text-slate-500">{{ $expense->incurred_on->format('d M Y') }}</p>
                    <p class="mt-2 text-sm text-slate-600">{{ $expense->description }}</p>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No expenses recorded.</div>
            @endforelse
        </div>
    </div>
    <div class="mt-5">{{ $expenses->links() }}</div>
</x-layouts.app>
