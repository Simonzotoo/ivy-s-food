@php
    $commitPermissions = [
        'customers' => 'imports.commit_customers',
        'suppliers' => 'imports.commit_suppliers',
        'raw_materials' => 'imports.commit_catalogue',
        'product_variants' => 'imports.commit_catalogue',
        'barcodes' => 'imports.commit_catalogue',
        'prices' => 'imports.commit_catalogue',
        'opening_stock' => 'imports.commit_opening_stock',
        'open_credit_balances' => 'imports.commit_opening_stock',
    ];
    $canCommit = auth()->user()->can($commitPermissions[$batch->import_type] ?? 'imports.view');
@endphp
<x-layouts.app title="Import: {{ $batch->batch_number }}">
    <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase text-slate-500">{{ $label }}</p>
                <h2 class="mt-1 text-xl font-bold text-ivy-navy">{{ $batch->batch_number }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $batch->original_filename ?? 'No file on record' }}</p>
                <span class="badge mt-2 {{ str_contains($batch->status, 'error') || str_contains($batch->status, 'blocked') ? 'badge-danger' : (in_array($batch->status, ['applied', 'rolled_back'], true) ? 'badge-success' : 'badge-warning') }}">{{ str_replace('_', ' ', $batch->status) }}</span>
            </div>
            @can('imports.rollback')
                @if (in_array($batch->status, ['applied', 'applied_with_errors'], true))
                    <x-confirm-form
                        action="{{ route('imports.rollback', $batch) }}"
                        trigger="Roll back"
                        button-class="btn-secondary"
                        message="Roll back this import? Only newly created records with no later sales, invoices or payments will be removed."
                        confirm-label="Yes, roll back"
                    />
                @endif
            @endcan
        </div>

        <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
            <div class="metric-card p-3"><p class="metric-label">Total rows</p><p class="mt-1 text-xl font-bold text-ivy-navy">{{ $batch->total_rows }}</p></div>
            <div class="metric-card p-3"><p class="metric-label">Valid</p><p class="mt-1 text-xl font-bold text-ivy-navy">{{ $batch->valid_rows }}</p></div>
            <div class="metric-card p-3"><p class="metric-label">Invalid</p><p class="mt-1 text-xl font-bold text-ivy-navy">{{ $batch->error_rows }}</p></div>
            <div class="metric-card p-3"><p class="metric-label">Duplicate</p><p class="mt-1 text-xl font-bold text-ivy-navy">{{ $batch->duplicate_rows }}</p></div>
            <div class="metric-card p-3"><p class="metric-label">New</p><p class="mt-1 text-xl font-bold text-ivy-navy">{{ $batch->new_rows }}</p></div>
            <div class="metric-card p-3"><p class="metric-label">Updates</p><p class="mt-1 text-xl font-bold text-ivy-navy">{{ $batch->update_rows }}</p></div>
            <div class="metric-card p-3"><p class="metric-label">Skipped</p><p class="mt-1 text-xl font-bold text-ivy-navy">{{ $batch->skipped_rows }}</p></div>
        </div>

        @if ($batch->duplicate_strategy)
            <p class="panel-help mt-4">Duplicate option selected: <strong>{{ ['skip' => 'Skip duplicate rows', 'update' => 'Update matching records', 'stop' => 'Stop and review'][$batch->duplicate_strategy] ?? $batch->duplicate_strategy }}</strong></p>
        @endif

        @if (in_array($batch->status, ['previewed', 'previewed_with_errors'], true))
            @if ($canCommit)
                @if ($batch->duplicate_strategy === 'stop' && $batch->duplicate_rows > 0)
                    <div class="mt-6 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        This import cannot be committed yet: {{ $batch->duplicate_rows }} row(s) matched existing customers and "Stop and review" is selected. Review the file, or go back and choose Skip or Update instead.
                    </div>
                @elseif ($batch->valid_rows > 0)
                    <div class="mt-6 rounded-md border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm text-slate-600">Ready to import {{ $batch->valid_rows }} row(s): {{ $batch->new_rows }} new, {{ $batch->update_rows }} updates.</p>
                        <div class="mt-3">
                            <x-confirm-form
                                action="{{ route('imports.commit', $batch) }}"
                                trigger="Confirm and import"
                                message="Import {{ $batch->valid_rows }} row(s) now? This will create or update real records and cannot be undone for updated rows."
                                confirm-label="Yes, import now"
                            />
                        </div>
                    </div>
                @else
                    <div class="mt-6 rounded-md border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">There are no valid rows to import. Fix the errors below and upload again.</div>
                @endif
            @else
                <div class="mt-6 rounded-md border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">You do not have permission to commit this type of import. Ask a manager to review and confirm it.</div>
            @endif
        @endif

        <div class="mt-8 flex items-center justify-between">
            <h3 class="text-base font-bold text-ivy-navy">Row errors</h3>
            @if ($batch->errors->isNotEmpty())
                <a class="metric-link" href="{{ route('imports.errors', $batch) }}">Download error file</a>
            @endif
        </div>
        <div class="mt-3 max-h-96 divide-y divide-slate-100 overflow-y-auto rounded-md border border-slate-200">
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
