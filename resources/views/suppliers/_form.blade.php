@csrf
@if ($supplier)
    @method('PUT')
@endif
<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label class="form-label" for="name">Supplier name</label>
        <input class="form-input" id="name" name="name" value="{{ old('name', $supplier->name ?? '') }}" required>
    </div>
    <div>
        <label class="form-label" for="contact_person">Contact person</label>
        <input class="form-input" id="contact_person" name="contact_person" value="{{ old('contact_person', $supplier->contact_person ?? '') }}">
    </div>
    <div>
        <label class="form-label" for="phone">Phone</label>
        <input class="form-input" id="phone" name="phone" value="{{ old('phone', $supplier->phone ?? '') }}">
    </div>
    <div>
        <label class="form-label" for="email">Email</label>
        <input class="form-input" id="email" name="email" type="email" value="{{ old('email', $supplier->email ?? '') }}">
    </div>
    <div>
        <label class="form-label" for="status">Status</label>
        <select class="form-input" id="status" name="status" required>
            @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $supplier->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="md:col-span-2">
        <label class="form-label" for="address">Address</label>
        <textarea class="form-input" id="address" name="address" rows="3">{{ old('address', $supplier->address ?? '') }}</textarea>
    </div>
    <div class="md:col-span-2">
        <label class="form-label" for="notes">Notes</label>
        <textarea class="form-input" id="notes" name="notes" rows="3">{{ old('notes', $supplier->notes ?? '') }}</textarea>
    </div>
</div>
<div class="mt-6 flex justify-end gap-3">
    <a href="{{ route('suppliers.index') }}" class="btn-secondary">Cancel</a>
    <button class="btn-primary" type="submit">Save supplier</button>
</div>
