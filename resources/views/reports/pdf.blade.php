<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { color: #101317; font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        table { border-collapse: collapse; margin-top: 16px; width: 100%; }
        th, td { border: 1px solid #d8dee8; padding: 6px; text-align: left; }
        th { background: #f6f8fb; color: #2d4054; }
    </style>
</head>
<body>
    <x-print-header :title="$title" :subtitle="$from->format('d M Y').' to '.$to->format('d M Y')" />
    <table>
        <thead>
            <tr>
                @foreach ($report['headers'] as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($report['rows'] as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($report['headers']) }}">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
