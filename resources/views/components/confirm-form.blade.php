@props(['action', 'message', 'trigger', 'confirmLabel' => 'Yes, continue', 'buttonClass' => 'btn-primary'])
<div x-data="{ open: false }" class="inline-block">
    <form x-ref="form" method="POST" action="{{ $action }}" @submit.prevent="open = true">
        @csrf
        <input type="hidden" name="confirm" value="1">
        {{ $slot }}
        <button type="submit" class="{{ $buttonClass }}">{{ $trigger }}</button>
    </form>
    <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-sm rounded-md bg-white p-5 shadow-lg" @click.outside="open = false">
            <p class="text-sm text-slate-700">{{ $message }}</p>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" class="btn-secondary" @click="open = false">Cancel</button>
                <button type="button" class="{{ $buttonClass }}" @click="open = false; $refs.form.submit()">{{ $confirmLabel }}</button>
            </div>
        </div>
    </div>
</div>
