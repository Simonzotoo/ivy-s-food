<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $broadcast->subject ?: $broadcast->name }}</title>
</head>
<body style="margin:0;padding:24px;background:#f6f8fb;font-family:Arial, Helvetica, sans-serif;color:#101317;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:8px;padding:24px;">
        <x-print-header :title="$broadcast->subject ?: $broadcast->name" />
        <p style="font-size:14px;line-height:1.6;white-space:pre-line;">{{ $broadcast->body }}</p>
        <x-print-footer />
    </div>
</body>
</html>
