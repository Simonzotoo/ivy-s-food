@csrf
<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label class="form-label" for="customer_type_id">Customer type</label>
        <select class="form-input" id="customer_type_id" name="customer_type_id" required>
            @foreach ($types as $type)
                <option value="{{ $type->id }}" @selected((string) old('customer_type_id', $customer->customer_type_id ?? '') === (string) $type->id)>{{ $type->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" for="default_sale_type_id">Default sale/pricing type</label>
        <select class="form-input" id="default_sale_type_id" name="default_sale_type_id">
            <option value="">Not set</option>
            @foreach ($saleTypes as $saleType)
                <option value="{{ $saleType->id }}" @selected((string) old('default_sale_type_id', $customer->default_sale_type_id ?? '') === (string) $saleType->id)>{{ $saleType->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" for="name">Customer name</label>
        <input class="form-input" id="name" name="name" value="{{ old('name', $customer->name ?? '') }}" required>
    </div>
    <div>
        <label class="form-label" for="contact_person">Contact person</label>
        <input class="form-input" id="contact_person" name="contact_person" value="{{ old('contact_person', $customer->contact_person ?? '') }}">
    </div>
    <div>
        <label class="form-label" for="phone">Phone</label>
        <input class="form-input" id="phone" name="phone" value="{{ old('phone', $customer->phone ?? '') }}">
    </div>
    <div>
        <label class="form-label" for="email">Email</label>
        <input class="form-input" id="email" name="email" type="email" value="{{ old('email', $customer->email ?? '') }}">
    </div>
    <div>
        <label class="form-label" for="location">Location</label>
        <input class="form-input" id="location" name="location" value="{{ old('location', $customer->location ?? '') }}">
    </div>
    <div>
        <label class="form-label" for="status">Status</label>
        <select class="form-input" id="status" name="status" required>
            @foreach (['active', 'inactive', 'suspended'] as $status)
                <option value="{{ $status }}" @selected(old('status', $customer->status ?? 'active') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    @can('customers.manage_credit')
        <div>
            <label class="form-label" for="credit_limit">Credit limit (GHS)</label>
            <input class="form-input" id="credit_limit" name="credit_limit" type="number" min="0" step="0.01" value="{{ old('credit_limit', $customer->credit_limit ?? '') }}">
        </div>
    @endcan
    <label class="mt-7 inline-flex items-center gap-2 text-sm font-semibold text-ivy-navy">
        <input type="checkbox" name="communication_consent" value="1" class="rounded border-slate-300 text-ivy-red focus:ring-ivy-red" @checked(old('communication_consent', $customer->communication_consent ?? false))>
        Communication consent recorded
    </label>
</div>
<div class="mt-5">
    <label class="form-label" for="address">Address</label>
    <textarea class="form-input" id="address" name="address" rows="3">{{ old('address', $customer->address ?? '') }}</textarea>
</div>
<div class="mt-5">
    <label class="form-label" for="notes">Notes</label>
    <textarea class="form-input" id="notes" name="notes" rows="3">{{ old('notes', $customer->notes ?? '') }}</textarea>
</div>
