@props([
    'name',
    'size' => 'size-5',
])

<svg
    aria-hidden="true"
    {{ $attributes->class([$size, 'shrink-0 fill-none stroke-current']) }}
    viewBox="0 0 24 24"
    stroke-width="1.8"
    stroke-linecap="round"
    stroke-linejoin="round"
>
    <use href="{{ asset('images/heroicons.svg') }}#{{ $name }}"></use>
</svg>
