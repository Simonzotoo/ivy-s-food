@props(['type', 'label'])
@can('imports.upload')
    <a href="{{ route('imports.create', $type) }}" class="btn-secondary">{{ $label }}</a>
@endcan
