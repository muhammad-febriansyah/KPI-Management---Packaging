@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
])

@php
    $variantClasses = match ($variant) {
        'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:border-slate-400 hover:bg-slate-50',
        'danger' => 'border border-danger bg-danger text-white hover:bg-red-700',
        'ghost' => 'border border-transparent bg-transparent text-slate-600 hover:bg-slate-100 hover:text-ink',
        default => 'border border-primary-600 bg-primary-600 text-white hover:border-primary-700 hover:bg-primary-700',
    };

    $sizeClasses = match ($size) {
        'sm' => 'h-9 px-3 text-xs',
        default => 'h-10 px-4 text-sm',
    };

    $classes = "inline-flex items-center justify-center gap-2 rounded-lg font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:cursor-not-allowed disabled:opacity-50 {$variantClasses} {$sizeClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$classes]) }}>{{ $slot }}</button>
@endif
