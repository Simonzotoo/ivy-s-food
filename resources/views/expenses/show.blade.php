<x-layouts.app title="Expense Detail">
    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase text-slate-500">{{ $expense->category->name }}</p>
                    <h2 class="mt-1 text-xl font-bold text-ivy-navy">{{ $expense->expense_number }}</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ $expense->description }}</p>
                </div>
                <p class="text-xl font-bold text-ivy-navy">GHS {{ $expense->amount }}</p>
            </div>
            <dl class="mt-6 grid gap-4 md:grid-cols-3">
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase text-slate-500">Date</dt><dd class="mt-1 font-bold text-ivy-navy">{{ $expense->incurred_on->format('d M Y') }}</dd></div>
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase text-slate-500">Method</dt><dd class="mt-1 font-bold text-ivy-navy">{{ $expense->payment_method }}</dd></div>
                <div class="rounded-md border border-slate-200 bg-slate-50 p-4"><dt class="text-xs font-semibold uppercase text-slate-500">Status</dt><dd class="mt-1 font-bold text-ivy-navy">{{ ucfirst($expense->status) }}</dd></div>
            </dl>
            @if ($expense->receipt_path || $expense->receipt_original_name)
                <div class="mt-6 rounded-md border border-slate-200 bg-slate-50 p-4 text-sm">
                    <h3 class="text-base font-bold text-ivy-navy">Receipt reference</h3>
                    <p class="mt-2 break-words text-slate-600">{{ $expense->receipt_original_name ?: 'Receipt' }}</p>
                    <p class="break-words text-xs text-slate-500">{{ $expense->receipt_path }}</p>
                </div>
            @endif
        </section>

        <aside class="space-y-4">
            <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-bold text-ivy-navy">Review trail</h3>
                <dl class="mt-3 space-y-3 text-sm">
                    <div><dt class="font-semibold text-slate-500">Recorded by</dt><dd>{{ $expense->creator?->name ?? 'Unknown' }}</dd></div>
                    <div><dt class="font-semibold text-slate-500">Reviewed by</dt><dd>{{ $expense->approver?->name ?? 'Pending' }}</dd></div>
                    <div><dt class="font-semibold text-slate-500">Reviewed at</dt><dd>{{ $expense->approved_at?->format('d M Y H:i') ?? 'Pending' }}</dd></div>
                    @if ($expense->rejection_reason)
                        <div><dt class="font-semibold text-slate-500">Rejection reason</dt><dd>{{ $expense->rejection_reason }}</dd></div>
                    @endif
                </dl>
            </div>
            @can('expenses.approve')
                @if ($expense->status === 'pending')
                    <form method="POST" action="{{ route('expenses.approve', $expense) }}" class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
                        @csrf
                        <button class="btn-primary w-full" type="submit">Approve expense</button>
                    </form>
                    <form method="POST" action="{{ route('expenses.reject', $expense) }}" class="space-y-3 rounded-md border border-slate-200 bg-white p-5 shadow-sm">
                        @csrf
                        <label class="form-label" for="rejection_reason">Rejection reason</label>
                        <textarea class="form-input" id="rejection_reason" name="rejection_reason" rows="3" required></textarea>
                        <button class="btn-secondary w-full" type="submit">Reject expense</button>
                    </form>
                @endif
            @endcan
        </aside>
    </div>
</x-layouts.app>
