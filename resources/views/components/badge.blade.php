@props(['variant' => 'neutral'])

@php
    $classes = match ($variant) {
        'success' => 'border-green-200 bg-green-50 text-green-700',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
        'danger' => 'border-red-200 bg-red-50 text-red-700',
        'info' => 'border-sky-200 bg-sky-50 text-sky-700',
        default => 'border-slate-200 bg-slate-50 text-slate-600',
    };
@endphp

<span {{ $attributes->class(["inline-flex items-center rounded-md border px-2 py-1 text-[11px] font-semibold {$classes}"]) }}>
    {{ $slot }}
</span>
