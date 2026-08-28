@php
    $company = \App\Models\CompanySetting::current();
    $contactParts = array_filter([$company->phone, $company->email]);
@endphp
<div style="margin-top:24px;padding-top:10px;border-top:1px solid #d8dee8;font-size:9px;color:#6b7280;text-align:center;">
    @if ($company->receipt_footer)
        <p style="margin:0 0 4px 0;">{{ $company->receipt_footer }}</p>
    @endif
    <p style="margin:0;">
        {{ $company->trading_name ?: "Ivy's Food" }}
        @if (count($contactParts))
            · {{ implode(' · ', $contactParts) }}
        @endif
    </p>
</div>
