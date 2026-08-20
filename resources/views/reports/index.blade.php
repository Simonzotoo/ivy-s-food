<x-layouts.app title="Reports">
    <form method="GET" class="mb-6 grid gap-3 rounded-md border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[1fr_160px_160px_auto]">
        <select class="form-input mt-0" name="type">
            @foreach ($reports as $key => $label)
                <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <input class="form-input mt-0" name="from" type="date" value="{{ request('from', $from->toDateString()) }}">
        <input class="form-input mt-0" name="to" type="date" value="{{ request('to', $to->toDateString()) }}">
        <button class="btn-secondary" type="submit">Apply</button>
    </form>

    <div class="mb-5 flex flex-wrap justify-end gap-2">
        @can('reports.export')
            <a class="btn-secondary" href="{{ route('reports.export.csv', request()->query()) }}">Excel CSV</a>
            <a class="btn-primary" href="{{ route('reports.export.pdf', request()->query()) }}">PDF</a>
        @endcan
    </div>

    <section class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-base font-bold text-ivy-navy">{{ $reports[$type] }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase text-slate-500">
                    <tr>
                        @foreach ($report['headers'] as $header)
                            <th class="px-4 py-3">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($report['rows'] as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td class="px-4 py-3">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td class="px-4 py-12 text-center text-slate-500" colspan="{{ count($report['headers']) }}">No records found for this report and period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.app>
