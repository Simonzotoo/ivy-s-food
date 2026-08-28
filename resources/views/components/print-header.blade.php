@props(['title', 'subtitle' => null])
@php
    $company = \App\Models\CompanySetting::current();
    $logoPath = $company->logo_path ? public_path($company->logo_path) : public_path('images/ivys-logo.png');
    $printLogo = is_file($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
@endphp
<table style="width:100%;border-collapse:collapse;margin-bottom:14px;">
    <tr>
        @if ($printLogo)
            <td style="width:56px;vertical-align:middle;padding:0;">
                <img src="data:image/png;base64,{{ $printLogo }}" style="height:48px;width:48px;object-fit:contain;">
            </td>
        @endif
        <td style="vertical-align:middle;padding-left:{{ $printLogo ? '12px' : '0' }};">
            <div style="font-size:19px;font-weight:bold;color:#2d4054;">{{ $company->trading_name ?: "Ivy's Food" }}</div>
            <div style="font-size:10px;color:#6b7280;">Management System</div>
        </td>
        <td style="text-align:right;vertical-align:middle;">
            <div style="font-size:14px;font-weight:bold;color:#d9110f;text-transform:uppercase;">{{ $title }}</div>
            @if ($subtitle)
                <div style="font-size:10px;color:#6b7280;">{{ $subtitle }}</div>
            @endif
        </td>
    </tr>
</table>
<div style="border-bottom:3px solid #d9110f;margin-bottom:14px;"></div>
