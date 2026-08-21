@php
    $source = $previewMode ? $preview : old();
@endphp
<div class="grid gap-5 lg:grid-cols-2">
    <div>
        <label class="form-label" for="name">Broadcast name <span class="text-ivy-red">*</span></label>
        <input class="form-input" id="name" name="name" value="{{ $source['name'] ?? '' }}" required>
    </div>
    <div>
        <label class="form-label" for="channel">Channel <span class="text-ivy-red">*</span></label>
        <select class="form-input" id="channel" name="channel" required>
            @foreach (['local' => 'Local test', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp'] as $value => $label)
                <option value="{{ $value }}" @selected(($source['channel'] ?? 'local') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
<div>
    <label class="form-label" for="body">Message <span class="text-ivy-red">*</span></label>
    <textarea class="form-input" id="body" name="body" rows="5" required>{{ $source['body'] ?? '' }}</textarea>
</div>
<div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
    SMS and WhatsApp broadcasts are logged through the local/test adapter until a real provider is approved.
</div>

@php
    $hasAudienceFilters = ($source['customer_type_id'] ?? null) || ($source['sale_type_id'] ?? null) || ($source['location'] ?? null)
        || (($source['purchase_history'] ?? 'any') !== 'any') || ($source['broadcast_template_id'] ?? null) || ($source['scheduled_for'] ?? null);
@endphp
<details class="rounded-md border border-slate-200 p-4" {{ $hasAudienceFilters ? 'open' : '' }}>
    <summary class="cursor-pointer text-sm font-semibold text-ivy-navy">More details (template, schedule, audience filters)</summary>
    <div class="mt-4 grid gap-5 lg:grid-cols-2">
        <div>
            <label class="form-label" for="broadcast_template_id">Template</label>
            <select class="form-input" id="broadcast_template_id" name="broadcast_template_id">
                <option value="">No template</option>
                @foreach ($templates as $template)
                    <option value="{{ $template->id }}" @selected((string) ($source['broadcast_template_id'] ?? '') === (string) $template->id)>{{ $template->name }}</option>
                @endforeach
            </select>
        </div>
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
        </div>
    </div>
</details>
