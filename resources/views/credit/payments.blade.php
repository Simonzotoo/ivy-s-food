<x-layouts.app title="Credit Payments">
    <form method="POST" action="{{ route('credit.payments.store') }}" class="mb-6 grid gap-4 rounded-md border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[1fr_140px_140px_1fr_auto]">
        @csrf
        <select class="form-input mt-0" name="customer_id" required>
            <option value="">Customer</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}">{{ $customer->name }} · GHS {{ $customer->outstanding_balance }}</option>
            @endforeach
        </select>
        <select class="form-input mt-0" name="payment_method" required>
            <option>Cash</option>
            <option>MoMo</option>
        </select>
        <input class="form-input mt-0" name="amount" type="number" min="0.01" step="0.01" placeholder="Amount" required>
        <input class="form-input mt-0" name="transaction_reference" placeholder="MoMo/reference">
        <button class="btn-primary" type="submit">Record</button>
    </form>
    <div class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($payments as $payment)
                <article class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="font-semibold text-ivy-navy">{{ $payment->payment_number }} · {{ $payment->customer->name }}</p>
                    <div class="flex items-center gap-2"><p>GHS {{ $payment->amount }}</p><a class="btn-secondary" href="{{ route('credit.payments.receipt', $payment) }}">Receipt PDF</a></div>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No customer payments recorded.</div>
            @endforelse
        </div>
    </div>
    <div class="mt-5">{{ $payments->links() }}</div>
</x-layouts.app>
