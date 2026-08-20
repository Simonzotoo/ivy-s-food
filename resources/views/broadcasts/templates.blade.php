<x-layouts.app title="Broadcast Templates">
    <form method="POST" action="{{ route('broadcasts.templates.store') }}" class="mb-6 grid gap-4 rounded-md border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[1fr_160px]">
        @csrf
        <input class="form-input mt-0" name="name" placeholder="Template name" required>
        <select class="form-input mt-0" name="channel" required><option value="local">Local</option><option value="sms">SMS</option><option value="whatsapp">WhatsApp</option></select>
        <input class="form-input mt-0 lg:col-span-2" name="subject" placeholder="Subject">
        <textarea class="form-input mt-0 lg:col-span-2" name="body" rows="4" placeholder="Message body" required></textarea>
        <button class="btn-primary lg:col-span-2" type="submit">Save template</button>
    </form>
    <section class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($templates as $template)
                <article class="p-5">
                    <p class="font-semibold text-ivy-navy">{{ $template->name }} · {{ strtoupper($template->channel) }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $template->subject ?: 'No subject' }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ $template->body }}</p>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No broadcast templates configured.</div>
            @endforelse
        </div>
    </section>
    <div class="mt-5">{{ $templates->links() }}</div>
</x-layouts.app>
