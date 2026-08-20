<x-layouts.app title="Import Detail">
    <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase text-slate-500">{{ $batch->import_type }}</p>
                <h2 class="mt-1 text-xl font-bold text-ivy-navy">{{ $batch->batch_number }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ $batch->status }} · {{ $batch->total_rows }} rows · {{ $batch->valid_rows }} valid · {{ $batch->error_rows }} errors</p>
            </div>
            @can('imports.rollback')
                @if (in_array($batch->status, ['applied', 'applied_with_errors'], true))
                    <form method="POST" action="{{ route('imports.rollback', $batch) }}">
                        @csrf
                        <button class="btn-secondary" type="submit">Rollback if supported</button>
                    </form>
                @endif
            @endcan
        </div>
        <h3 class="mt-8 text-base font-bold text-ivy-navy">Errors</h3>
        <div class="mt-3 divide-y divide-slate-100 rounded-md border border-slate-200">
            @forelse ($batch->errors as $error)
                <article class="p-4 text-sm">
                    <p class="font-semibold text-ivy-navy">Row {{ $error->row_number }}</p>
                    <p class="text-slate-600">{{ $error->message }}</p>
                </article>
            @empty
                <p class="p-4 text-sm text-slate-500">No validation errors.</p>
            @endforelse
        </div>
    </section>
</x-layouts.app>
