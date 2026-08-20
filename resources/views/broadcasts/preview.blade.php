<x-layouts.app title="Broadcast Preview">
    <div class="mb-5 rounded-md border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-semibold text-slate-500">Matching consenting recipients</p>
        <p class="mt-2 text-3xl font-bold text-ivy-navy">{{ $recipients->count() }}</p>
    </div>
    <form method="POST" action="{{ route('broadcasts.store') }}" class="space-y-6 rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @include('broadcasts.partials.form-fields', ['previewMode' => true])
        <section class="rounded-md border border-slate-200 bg-slate-50 p-4">
            <h2 class="text-base font-bold text-ivy-navy">Recipients</h2>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                @forelse ($recipients as $customer)
                    <p class="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm">{{ $customer->name }} · {{ $customer->type?->name ?? 'Unclassified' }} · {{ $customer->location ?: 'No location' }}</p>
                @empty
                    <p class="text-sm text-slate-500">No consenting recipients match these filters.</p>
                @endforelse
            </div>
        </section>
        <div class="flex flex-wrap justify-end gap-3">
            <a href="{{ route('broadcasts.create') }}" class="btn-secondary">Edit</a>
            <button class="btn-primary" type="submit" @disabled($recipients->isEmpty())>Save broadcast</button>
        </div>
    </form>
</x-layouts.app>
