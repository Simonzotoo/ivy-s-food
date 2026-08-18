<x-layouts.app title="Company Settings">
    <form method="POST" action="{{ route('company.update') }}" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div class="mb-6 flex items-center gap-4">
            <img src="{{ asset($settings->logo_path ?? 'images/ivys-logo.png') }}" alt="Ivy's Food" class="h-20 w-20 object-contain">
            <p class="text-sm text-slate-600">Unknown official details are intentionally blank for later completion.</p>
        </div>
        <div class="grid gap-5 md:grid-cols-2">
            @foreach ([
                'company_name' => 'Company name',
                'trading_name' => 'Trading name',
                'address' => 'Address',
                'phone' => 'Phone number',
                'email' => 'Email',
                'website' => 'Website',
                'receipt_footer' => 'Receipt footer',
                'tax_registration_info' => 'Tax or registration information',
            ] as $field => $label)
                <div class="{{ in_array($field, ['address', 'receipt_footer']) ? 'md:col-span-2' : '' }}">
                    <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                    @if (in_array($field, ['address', 'receipt_footer']))
                        <textarea class="form-input" id="{{ $field }}" name="{{ $field }}" rows="3">{{ old($field, $settings->{$field}) }}</textarea>
                    @else
                        <input class="form-input" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $settings->{$field}) }}" {{ $field === 'trading_name' ? 'required' : '' }}>
                    @endif
                </div>
            @endforeach
            <div>
                <label class="form-label" for="currency">Currency</label>
                <input class="form-input" id="currency" name="currency" value="{{ old('currency', $settings->currency) }}" required readonly>
            </div>
            <div>
                <label class="form-label" for="time_zone">Time zone</label>
                <input class="form-input" id="time_zone" name="time_zone" value="{{ old('time_zone', $settings->time_zone) }}" required readonly>
            </div>
            <div>
                <label class="form-label" for="default_credit_period_days">Default credit period</label>
                <input class="form-input" id="default_credit_period_days" name="default_credit_period_days" type="number" min="1" value="{{ old('default_credit_period_days', $settings->default_credit_period_days) }}" required>
            </div>
            <div>
                <label class="form-label" for="default_low_stock_threshold">Default low-stock threshold</label>
                <input class="form-input" id="default_low_stock_threshold" name="default_low_stock_threshold" type="number" min="0" value="{{ old('default_low_stock_threshold', $settings->default_low_stock_threshold) }}">
            </div>
            <div>
                <label class="form-label" for="default_expiry_alert_days">Default expiry-alert days</label>
                <input class="form-input" id="default_expiry_alert_days" name="default_expiry_alert_days" type="number" min="0" value="{{ old('default_expiry_alert_days', $settings->default_expiry_alert_days) }}">
            </div>
        </div>
        @can('company.edit')
            <div class="mt-6 flex justify-end">
                <button class="btn-primary" type="submit">Save settings</button>
            </div>
        @endcan
    </form>
</x-layouts.app>
