<x-layouts.app title="Audit Logs">
    <form method="GET" class="mb-5 flex gap-3 rounded-md border border-slate-200 bg-white p-4 shadow-sm">
        <input class="form-input mt-0" name="event" value="{{ request('event') }}" placeholder="Filter by event">
        <button class="btn-secondary" type="submit">Filter</button>
    </form>
    <div class="overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Event</th>
                        <th class="px-4 py-3">Actor</th>
                        <th class="px-4 py-3">Record</th>
                        <th class="px-4 py-3">Context</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3">{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3 font-semibold text-ivy-navy">{{ $log->event }}</td>
                            <td class="px-4 py-3">{{ $log->actor?->name ?? 'System / unknown' }}</td>
                            <td class="px-4 py-3">{{ class_basename((string) $log->auditable_type) }} #{{ $log->auditable_id }}</td>
                            <td class="max-w-md px-4 py-3 text-xs text-slate-600">
                                <pre class="whitespace-pre-wrap">{{ json_encode(['old' => $log->old_values, 'new' => $log->new_values, 'metadata' => $log->metadata], JSON_PRETTY_PRINT) }}</pre>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No audit entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 px-4 py-3">{{ $logs->links() }}</div>
    </div>
</x-layouts.app>
