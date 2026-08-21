@props(['name', 'options', 'selected' => null, 'placeholder' => 'Search...', 'required' => false, 'emptyLabel' => null])
{{--
    options: array of ['value' => scalar, 'label' => string, 'sublabel' => string|null]
    Renders a text search box over the given options plus a hidden input carrying the
    chosen value, so it posts exactly like a normal <select> while staying usable with
    long product/material/supplier/customer lists.
--}}
@once
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('searchableSelect', (options, selectedValue) => ({
                query: '',
                open: false,
                value: selectedValue !== null && selectedValue !== undefined ? String(selectedValue) : '',
                options: options,
                init() {
                    const match = this.options.find((o) => String(o.value) === this.value);
                    this.query = match ? match.label : '';
                },
                get filtered() {
                    const selectedLabel = this.options.find((o) => String(o.value) === this.value)?.label ?? '';
                    if (!this.query || this.query === selectedLabel) {
                        return this.options.slice(0, 50);
                    }
                    const q = this.query.toLowerCase();

                    return this.options.filter((o) => o.label.toLowerCase().includes(q)
                        || (o.sublabel ?? '').toLowerCase().includes(q)).slice(0, 50);
                },
                select(option) {
                    this.value = String(option.value);
                    this.query = option.label;
                    this.open = false;
                    this.$refs.input.dispatchEvent(new CustomEvent('searchable-select:change', { bubbles: true, detail: option }));
                },
                clear() {
                    this.value = '';
                    this.query = '';
                    this.$refs.input.dispatchEvent(new CustomEvent('searchable-select:change', { bubbles: true, detail: null }));
                },
            }));
        });
    </script>
@endonce
<div x-data="searchableSelect(@js($options), @js($selected))" class="relative" {{ $attributes }}>
    <input type="hidden" name="{{ $name }}" x-bind:value="value">
    <div class="relative">
        <input
            x-ref="input"
            type="text"
            class="form-input mt-0 pr-9"
            x-model="query"
            @focus="open = true"
            @click.outside="open = false"
            @keydown.escape="open = false"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            x-bind:required="{{ $required ? 'true' : 'false' }} && !value"
        >
        <button type="button" class="absolute inset-y-0 right-2 text-slate-400 hover:text-slate-600" x-show="value" x-cloak @click="clear()" aria-label="Clear selection">&times;</button>
    </div>
    <div x-show="open && filtered.length" x-cloak class="absolute z-20 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-slate-200 bg-white py-1 shadow-lg">
        <template x-for="option in filtered" :key="option.value">
            <button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" @click="select(option)">
                <span x-text="option.label"></span>
                <span class="block text-xs text-slate-500" x-show="option.sublabel" x-text="option.sublabel"></span>
            </button>
        </template>
    </div>
    <div x-show="open && !filtered.length" x-cloak class="absolute z-20 mt-1 w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500 shadow-lg">
        {{ $emptyLabel ?? 'No matches found.' }}
    </div>
</div>
