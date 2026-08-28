<x-layouts.app title="Communication">
    <p class="mb-5 text-sm text-slate-500">Send a message to your customers by SMS, email, or both at once.</p>
    <form method="POST" action="{{ route('broadcasts.store') }}" class="space-y-6 rounded-md border border-slate-200 bg-white p-6 shadow-sm">
        @csrf
        @include('broadcasts.partials.form-fields', ['previewMode' => false])
        <div class="flex flex-wrap justify-end gap-3">
            <button class="btn-secondary" type="submit" formaction="{{ route('broadcasts.preview') }}">Preview recipients</button>
            <button class="btn-primary" type="submit">Send broadcast</button>
        </div>
    </form>
</x-layouts.app>
