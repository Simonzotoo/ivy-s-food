<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function edit(): View
    {
        return view('company.edit', ['settings' => CompanySetting::current()]);
    }

    public function update(Request $request, AuditService $audit): RedirectResponse
    {
        $settings = CompanySetting::current();
        $data = $request->validate([
            'company_name' => ['nullable', 'string', 'max:255'],
            'trading_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'currency' => ['required', Rule::in(['GHS'])],
            'time_zone' => ['required', Rule::in(['Africa/Accra'])],
            'receipt_footer' => ['nullable', 'string', 'max:1000'],
            'tax_registration_info' => ['nullable', 'string', 'max:255'],
            'default_credit_period_days' => ['required', 'integer', 'min:1', 'max:365'],
            'default_low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'default_expiry_alert_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
        ]);

        $old = $settings->only(array_keys($data));
        $settings->update($data);
        $audit->record('company_settings.updated', $settings, $old, $settings->fresh()->only(array_keys($data)), actor: $request->user());

        return back()->with('status', 'Company settings updated.');
    }
}
