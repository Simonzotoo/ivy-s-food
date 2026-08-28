@php
    $source = $previewMode ? $preview : old();
    $selectedChannels = $source['channels'] ?? ['sms'];
@endphp
<div class="grid gap-5 lg:grid-cols-2">
    <div>
        <label class="form-label" for="name">Broadcast name <span class="text-ivy-red">*</span></label>
        <input class="form-input" id="name" name="name" value="{{ $source['name'] ?? '' }}" required>
    </div>
    <div>
        <span class="form-label">Send via <span class="text-ivy-red">*</span></span>
        <div class="mt-1 flex gap-5">
            @foreach (['sms' => 'SMS', 'email' => 'Email'] as $value => $label)
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="channels[]" value="{{ $value }}" class="rounded border-slate-300 text-ivy-red focus:ring-ivy-red" @checked(in_array($value, $selectedChannels, true))>
                    {{ $label }}
                </label>
            @endforeach
        </div>
        <p class="mt-1 text-xs text-slate-500">Tick both to send the same message by SMS and email at once.</p>
    </div>
</div>
<div>
    <label class="form-label" for="body">Message <span class="text-ivy-red">*</span></label>
    <textarea class="form-input" id="body" name="body" rows="5" required>{{ $source['body'] ?? '' }}</textarea>
</div>
<div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
    SMS messages are sent via mNotify. Email messages are sent to each customer's email address on file. Customers without a phone number or email for the chosen channel are skipped automatically.
</div>

@php
    $hasAudienceFilters = ($source['customer_type_id'] ?? null) || ($source['sale_type_id'] ?? null) || ($source['location'] ?? null)
        || (($source['purchase_history'] ?? 'any') !== 'any') || ($source['scheduled_for'] ?? null);
@endphp
<details class="rounded-md border border-slate-200 p-4" {{ $hasAudienceFilters ? 'open' : '' }}>
    <summary class="cursor-pointer text-sm font-semibold text-ivy-navy">More details (schedule, audience filters)</summary>
    <div class="mt-4 grid gap-5 lg:grid-cols-2">
        <div>
            <label class="form-label" for="scheduled_for">Schedule for</label>
            <input class="form-input" id="scheduled_for" name="scheduled_for" type="datetime-local" value="{{ $source['scheduled_for'] ?? '' }}">
            <p class="mt-1 text-xs text-slate-500">Leave blank to send as soon as it's created.</p>
        </div>
        <div>
            <label class="form-label" for="customer_type_id">Customer type</label>
            <select class="form-input" id="customer_type_id" name="customer_type_id">
                <option value="">All types</option>
                @foreach ($customerTypes as $type)
                    <option value="{{ $type->id }}" @selected((string) ($source['customer_type_id'] ?? '') === (string) $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="sale_type_id">Default sale type</label>
            <select class="form-input" id="sale_type_id" name="sale_type_id">
                <option value="">All sale types</option>
                @foreach ($saleTypes as $type)
                    <option value="{{ $type->id }}" @selected((string) ($source['sale_type_id'] ?? '') === (string) $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label" for="location">Location contains</label>
            <input class="form-input" id="location" name="location" value="{{ $source['location'] ?? '' }}">
        </div>
        <div>
            <label class="form-label" for="purchase_history">Purchase history</label>
            <select class="form-input" id="purchase_history" name="purchase_history" required>
                @foreach (['any' => 'Any', 'has_purchases' => 'Has purchases', 'no_purchases' => 'No purchases'] as $value => $label)
                    <option value="{{ $value }}" @selected(($source['purchase_history'] ?? 'any') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="lg:col-span-2">
            <label class="form-label" for="subject">Subject</label>
            <input class="form-input" id="subject" name="subject" value="{{ $source['subject'] ?? '' }}">
            <p class="mt-1 text-xs text-slate-500">Used as the email subject line. Ignored for SMS.</p>
        </div>
    </div>
</details>
