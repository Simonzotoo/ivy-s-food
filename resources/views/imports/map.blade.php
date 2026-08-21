<x-layouts.app title="Match Columns">
    <div class="mx-auto max-w-3xl">
        <section class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-bold text-ivy-navy">Match your file's columns</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $batch->original_filename }} · we matched columns automatically where we could. Check each one and adjust if needed.</p>

            <form method="POST" action="{{ route('imports.map.store', $batch) }}" class="mt-6 space-y-4">
                @csrf

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600">
                            <tr>
                                <th class="px-4 py-3">Field</th>
                                <th class="px-4 py-3">Column in your file</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($fields as $key => $field)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-ivy-navy">
                                        {{ $field['label'] }}
                                        @if ($field['required'])
                                            <span class="text-ivy-red">*</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <select class="form-input mt-0" name="mapping[{{ $key }}]">
                                            <option value="">Not in this file</option>
                                            @foreach ($headers as $header)
                                                <option value="{{ $header }}" @selected(($suggested[$key] ?? null) === $header)>{{ $header }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($batch->import_type === 'customers')
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                        <p class="form-label">If a row matches an existing customer's phone or email</p>
                        <p class="panel-help">Choose what should happen. "Stop and review" is safest and is selected by default.</p>
                        <div class="mt-3 space-y-2 text-sm">
                            <label class="flex items-center gap-2"><input type="radio" name="duplicate_strategy" value="stop" checked> Stop and review - do not import any duplicate rows until I check them</label>
                            <label class="flex items-center gap-2"><input type="radio" name="duplicate_strategy" value="skip"> Skip duplicate rows and continue with the rest</label>
                            <label class="flex items-center gap-2"><input type="radio" name="duplicate_strategy" value="update"> Update the matching customer record with the new details</label>
                        </div>
                    </div>
                @endif

                <div class="flex justify-end gap-3">
                    <a class="btn-secondary" href="{{ route('imports.index') }}">Cancel</a>
                    <button class="btn-primary" type="submit">Preview import</button>
                </div>
            </form>
        </section>
    </div>
</x-layouts.app>
