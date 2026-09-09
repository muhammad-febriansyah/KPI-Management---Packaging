@props([
    'name',
    'value' => null,
    'placeholder' => '0',
    'required' => false,
    'decimals' => 0,
])

<div class="relative" data-rupiah-field data-rupiah-decimals="{{ $decimals }}">
    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-medium text-slate-500">Rp</span>
    <input type="hidden" name="{{ $name }}" data-rupiah-raw value="{{ is_numeric($value) ? $value : '' }}">
    <input
        type="text"
        inputmode="decimal"
        autocomplete="off"
        data-rupiah-display
        placeholder="{{ $placeholder }}"
        @if ($required) required @endif
        {{ $attributes->class(['h-11 w-full rounded-lg border border-line pl-10 pr-3 font-normal']) }}
    >
</div>
