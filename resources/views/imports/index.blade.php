<x-layouts.app title="Data Imports">
    <p class="mb-6 max-w-3xl text-sm text-slate-600">Import business records from a CSV or Excel (.xlsx) file. Each import is previewed before anything is saved, so you can check for problems first.</p>

    <section class="mb-6 rounded-md border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-bold text-ivy-navy">Start an import</h2>
        <p class="mt-1 text-sm text-slate-500">Download a template, fill it in, then upload it.</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($types as $key => $spec)
                <div class="rounded-md border border-slate-200 p-4">
                    <p class="font-semibold text-ivy-navy">{{ $spec['label'] }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @can('imports.templates')
                            <a class="btn-secondary" href="{{ route('imports.template', $key) }}">Download template</a>
                        @endcan
                        @can('imports.upload')
                            <a class="btn-primary" href="{{ route('imports.create', $key) }}">Upload file</a>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    @can('imports.history')
        <section class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5">
                <h2 class="text-base font-bold text-ivy-navy">Import history</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($batches as $batch)
                    <article class="flex flex-col gap-2 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <a class="font-semibold text-ivy-navy" href="{{ route('imports.show', $batch) }}">{{ $batch->batch_number }}</a>
                            <p class="text-sm text-slate-600">{{ $types[$batch->import_type]['label'] ?? $batch->import_type }} · {{ $batch->original_filename ?? 'Pasted data' }}</p>
                        </div>
                        <span class="badge">{{ str_replace('_', ' ', $batch->status) }}</span>
                    </article>
                @empty
                    <div class="px-5 py-12 text-center text-slate-500">No imports have been run yet.</div>
                @endforelse
            </div>
            <div class="p-5">{{ $batches->links() }}</div>
        </section>
    @endcan
</x-layouts.app>
