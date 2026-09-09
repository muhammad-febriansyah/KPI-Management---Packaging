@props(['label' => 'Data'])

<div {{ $attributes }}>
    @if (isset($filters) || isset($actions))
        <div class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row sm:items-end sm:justify-between sm:p-5">
            @isset($filters)
                <div class="flex flex-1 flex-wrap items-end gap-3">{{ $filters }}</div>
            @endisset

            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full min-w-180 border-collapse text-left text-[13px]" aria-label="{{ $label }}">
            {{ $slot }}
        </table>
    </div>

    @isset($footer)
        <div class="border-t border-line px-4 py-3 sm:px-5">{{ $footer }}</div>
    @endisset
</div>
