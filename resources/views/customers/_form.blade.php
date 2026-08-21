@csrf
<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label class="form-label" for="name">Customer name <span class="text-ivy-red">*</span></label>
        <input class="form-input" id="name" name="name" value="{{ old('name', $customer->name ?? '') }}" required>
    </div>
    <div>
        <label class="form-label" for="customer_type_id">Customer type <span class="text-ivy-red">*</span></label>
        <select class="form-input" id="customer_type_id" name="customer_type_id" required>
            @foreach ($types as $type)
                <option value="{{ $type->id }}" @selected((string) old('customer_type_id', $customer->customer_type_id ?? '') === (string) $type->id)>{{ $type->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" for="phone">Phone number</label>
        <input class="form-input" id="phone" name="phone" value="{{ old('phone', $customer->phone ?? '') }}" placeholder="024xxxxxxx">
        <p class="mt-1 text-xs text-slate-500">Used to spot duplicate customers - add it if you have it.</p>
    </div>
    <div>
        <label class="form-label" for="default_sale_type_id">Default pricing (sale type)</label>
        <select class="form-input" id="default_sale_type_id" name="default_sale_type_id">
            <option value="">Not set</option>
            @foreach ($saleTypes as $saleType)
                <option value="{{ $saleType->id }}" @selected((string) old('default_sale_type_id', $customer->default_sale_type_id ?? '') === (string) $saleType->id)>{{ $saleType->name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">This is pre-selected at the till, but can be changed per sale.</p>
    </div>
    @can('customers.manage_credit')
        <div>
            <label class="form-label" for="credit_limit">Credit limit (GHS)</label>
            <input class="form-input" id="credit_limit" name="credit_limit" type="number" min="0" step="0.01" value="{{ old('credit_limit', $customer->credit_limit ?? '') }}">
            <p class="mt-1 text-xs text-slate-500">Leave blank if this customer is not allowed credit.</p>
        </div>
    @endcan
    <div>
        <label class="form-label" for="status">Account status</label>
        <select class="form-input" id="status" name="status" required>
            @foreach (['active', 'inactive', 'suspended'] as $status)
                <option value="{{ $status }}" @selected(old('status', $customer->status ?? 'active') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
</div>

@php
    $hasMoreDetails = old('contact_person', $customer->contact_person ?? null)
        || old('email', $customer->email ?? null)
        || old('location', $customer->location ?? null)
        || old('address', $customer->address ?? null)
        || old('notes', $customer->notes ?? null);
@endphp
<details class="mt-6 rounded-md border border-slate-200 p-4" {{ $hasMoreDetails ? 'open' : '' }}>
    <summary class="cursor-pointer text-sm font-semibold text-ivy-navy">More details (contact person, email, address, notes)</summary>
    <div class="mt-4 grid gap-5 md:grid-cols-2">
        <div>
            <label class="form-label" for="contact_person">Contact person</label>
            <input class="form-input" id="contact_person" name="contact_person" value="{{ old('contact_person', $customer->contact_person ?? '') }}">
        </div>
        <div>
            <label class="form-label" for="email">Email</label>
            <input class="form-input" id="email" name="email" type="email" value="{{ old('email', $customer->email ?? '') }}">
        </div>
        <div>
            <label class="form-label" for="location">Location</label>
            <input class="form-input" id="location" name="location" value="{{ old('location', $customer->location ?? '') }}" placeholder="e.g. Kumasi">
        </div>
        <label class="mt-7 inline-flex items-center gap-2 text-sm font-semibold text-ivy-navy">
            <input type="checkbox" name="communication_consent" value="1" class="rounded border-slate-300 text-ivy-red focus:ring-ivy-red" @checked(old('communication_consent', $customer->communication_consent ?? false))>
            Customer has agreed to receive messages
        </label>
    </div>
    <div class="mt-5">
        <label class="form-label" for="address">Address</label>
        <textarea class="form-input" id="address" name="address" rows="2">{{ old('address', $customer->address ?? '') }}</textarea>
    </div>
    <div class="mt-5">
        <label class="form-label" for="notes">Notes</label>
        <textarea class="form-input" id="notes" name="notes" rows="2">{{ old('notes', $customer->notes ?? '') }}</textarea>
    </div>
</details>
