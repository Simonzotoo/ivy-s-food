<x-layouts.app title="Credit Reminders">
    <form method="POST" action="{{ route('credit.reminders.templates.store') }}" class="mb-6 grid gap-4 rounded-md border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[180px_120px_160px_1fr_auto]">
        @csrf
        <input class="form-input mt-0" name="name" placeholder="Template name" required>
        <select class="form-input mt-0" name="channel" required><option value="local">Local</option><option value="sms">SMS</option><option value="whatsapp">WhatsApp</option></select>
        <select class="form-input mt-0" name="trigger_status" required><option value="due_soon">Due soon</option><option value="due_today">Due today</option><option value="overdue">Overdue</option></select>
        <input class="form-input mt-0" name="body" placeholder="Message body" required>
        <button class="btn-primary" type="submit">Save</button>
    </form>
    <div class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($reminders as $reminder)
                <article class="p-5 text-sm">
                    <p class="font-semibold text-ivy-navy">{{ $reminder->invoice->invoice_number }} · {{ ucfirst($reminder->channel) }} · {{ ucfirst($reminder->status) }}</p>
                    <p class="mt-1 text-slate-600">{{ $reminder->message }}</p>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No reminders logged yet.</div>
            @endforelse
        </div>
    </div>
</x-layouts.app>
