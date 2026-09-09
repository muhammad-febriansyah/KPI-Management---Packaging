@props([
    'name',
    'label',
    'required' => false,
    'hint' => null,
])

<div>
    <label for="{{ $attributes->get('id', $name) }}" class="mb-1.5 block text-sm font-medium text-slate-700">
        {{ $label }}
        @if ($required)
            <span class="text-danger" aria-hidden="true">*</span>
            <span class="sr-only">(wajib)</span>
        @endif
    </label>

    <select
        id="{{ $attributes->get('id', $name) }}"
        name="{{ $name }}"
        @if ($required) required @endif
        {{ $attributes->except('id')->class([
            'h-11 w-full rounded-lg border bg-white px-3.5 text-sm text-ink outline-none transition focus:border-primary-600 focus:ring-3 focus:ring-primary-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-muted',
            'border-danger' => $errors->has($name),
            'border-slate-300' => ! $errors->has($name),
        ]) }}
    >
        {{ $slot }}
    </select>

    @error($name)
        <p class="mt-1.5 text-xs text-danger">{{ $message }}</p>
    @else
        @if ($hint)
            <p class="mt-1.5 text-xs text-muted">{{ $hint }}</p>
        @endif
    @enderror
</div>
