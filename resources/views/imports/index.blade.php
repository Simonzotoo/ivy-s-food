<x-layouts.app title="Imports">
    @can('imports.run')
        <form method="POST" action="{{ route('imports.store') }}" class="mb-6 space-y-4 rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            <div class="grid gap-4 lg:grid-cols-[1fr_160px]">
                <select class="form-input mt-0" name="import_type" required>
                    @foreach ($types as $key => [$label])
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select class="form-input mt-0" name="mode" required><option value="dry_run">Dry run</option><option value="apply">Apply</option></select>
            </div>
            <textarea class="form-input font-mono" name="csv_content" rows="8" placeholder="Paste CSV content including the header row" required></textarea>
            <div class="flex justify-end"><button class="btn-primary" type="submit">Process import</button></div>
        </form>
    @endcan

    <section class="mb-6 rounded-md border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-bold text-ivy-navy">Templates</h2>
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach ($types as $key => [$label])
                <a class="btn-secondary" href="{{ route('imports.template', $key) }}">{{ $label }}</a>
            @endforeach
        </div>
    </section>

    <section class="rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($batches as $batch)
                <article class="flex flex-col gap-2 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <a class="font-semibold text-ivy-navy" href="{{ route('imports.show', $batch) }}">{{ $batch->batch_number }}</a>
                        <p class="text-sm text-slate-600">{{ $types[$batch->import_type][0] ?? $batch->import_type }} · {{ $batch->status }}</p>
                    </div>
                    <p class="text-sm text-slate-600">{{ $batch->valid_rows }} valid · {{ $batch->error_rows }} errors</p>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No imports have been run.</div>
            @endforelse
        </div>
    </section>
    <div class="mt-5">{{ $batches->links() }}</div>
</x-layouts.app>
