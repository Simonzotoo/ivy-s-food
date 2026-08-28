@props(['action', 'message', 'trigger', 'confirmLabel' => 'Yes, continue', 'buttonClass' => 'btn-primary'])
<form
    method="POST"
    action="{{ $action }}"
    class="inline-block"
    x-data
    @submit.prevent="window.ivyConfirm(@js($message), @js($confirmLabel)).then((ok) => ok && $el.submit())"
>
    @csrf
    <input type="hidden" name="confirm" value="1">
    {{ $slot }}
    <button type="submit" class="{{ $buttonClass }}">{{ $trigger }}</button>
</form>
