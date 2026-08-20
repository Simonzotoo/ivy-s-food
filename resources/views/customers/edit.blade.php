<x-layouts.app title="Edit Customer">
    <form method="POST" action="{{ route('customers.update', $customer) }}" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        @method('PUT')
        @include('customers._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('customers.show', $customer) }}" class="btn-secondary">Cancel</a>
            <button class="btn-primary" type="submit">Save changes</button>
        </div>
    </form>
</x-layouts.app>
