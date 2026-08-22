<x-layouts.app title="Message Templates">
    <div class="mb-6 rounded-md border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-bold text-ivy-navy">Create a message template</h2>
        <p class="mt-1 text-sm text-slate-500">Templates can be selected when sending a customer message.</p>
        <form method="POST" action="{{ route('broadcasts.templates.store') }}" class="mt-4 grid gap-4 lg:grid-cols-2">
            @csrf
            <div>
                <label class="form-label" for="name">Template name</label>
                <input class="form-input" id="name" name="name" placeholder="e.g. New product announcement" required>
            </div>
            <div>
                <label class="form-label" for="channel">Channel</label>
                <select class="form-input" id="channel" name="channel" required>
                    <option value="local">Local</option>
                    <option value="sms">SMS</option>
                    <option value="whatsapp">WhatsApp</option>
                </select>
            </div>
            <div class="lg:col-span-2">
                <label class="form-label" for="subject">Subject</label>
                <input class="form-input" id="subject" name="subject" placeholder="Optional">
            </div>
            <div class="lg:col-span-2">
                <label class="form-label" for="body">Message</label>
                <textarea class="form-input" id="body" name="body" rows="4" required></textarea>
            </div>
            <button class="btn-primary lg:col-span-2" type="submit">Save template</button>
        </form>
    </div>
    <section class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($templates as $template)
                <article class="p-5">
                    <p class="font-semibold text-ivy-navy">{{ $template->name }} · {{ strtoupper($template->channel) }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $template->subject ?: 'No subject' }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ $template->body }}</p>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No message templates configured.</div>
            @endforelse
        </div>
    </section>
    <div class="mt-5">{{ $templates->links() }}</div>
</x-layouts.app>
