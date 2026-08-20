<x-layouts.app title="Create Customer">
    <form method="POST" action="{{ route('customers.store') }}" class="rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        @include('customers._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('customers.index') }}" class="btn-secondary">Cancel</a>
            <button class="btn-primary" type="submit">Save customer</button>
        </div>
    </form>
</x-layouts.app>
