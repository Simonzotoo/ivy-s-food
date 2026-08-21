<x-layouts.app title="Reminders">
    <div class="mb-6 rounded-md border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-bold text-ivy-navy">Create a reminder template</h2>
        <p class="mt-1 text-sm text-slate-500">Templates can be selected when logging a reminder from a customer's credit invoice.</p>
        <form method="POST" action="{{ route('credit.reminders.templates.store') }}" class="mt-4 grid gap-4 lg:grid-cols-[180px_130px_150px_1fr_auto]">
            @csrf
            <div>
                <label class="form-label" for="name">Name</label>
                <input class="form-input" id="name" name="name" placeholder="e.g. Friendly reminder" required>
            </div>
            <div>
                <label class="form-label" for="channel">Channel</label>
                <select class="form-input" id="channel" name="channel" required>
                    <option value="local">Local</option>
                    <option value="sms">SMS</option>
                    <option value="whatsapp">WhatsApp</option>
                </select>
            </div>
            <div>
                <label class="form-label" for="trigger_status">Send when</label>
                <select class="form-input" id="trigger_status" name="trigger_status" required>
                    <option value="due_soon">Due soon</option>
                    <option value="due_today">Due today</option>
                    <option value="overdue">Overdue</option>
                </select>
            </div>
            <div>
                <label class="form-label" for="body">Message</label>
                <input class="form-input" id="body" name="body" placeholder="Message body" required>
            </div>
            <button class="btn-primary self-end" type="submit">Save</button>
        </form>
    </div>
    <div class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($reminders as $reminder)
                <article class="p-5 text-sm">
                    <p class="font-semibold text-ivy-navy">{{ $reminder->invoice->invoice_number }} · {{ ucfirst($reminder->channel) }} · <span class="badge">{{ ucfirst($reminder->status) }}</span></p>
                    <p class="mt-1 text-slate-600">{{ $reminder->message }}</p>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No reminders logged yet.</div>
            @endforelse
        </div>
    </div>
</x-layouts.app>
