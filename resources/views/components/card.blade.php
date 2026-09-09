@props([
    'title' => null,
    'description' => null,
    'padding' => true,
])

<section {{ $attributes->class(['rounded-card border border-line bg-surface shadow-card']) }}>
    @if ($title || $description || isset($actions))
        <header class="flex flex-col gap-3 border-b border-line px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                @if ($title)
                    <h2 class="text-base font-semibold text-ink">{{ $title }}</h2>
                @endif

                @if ($description)
                    <p class="mt-1 text-xs leading-5 text-muted">{{ $description }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['p-4 sm:p-5' => $padding])>
        {{ $slot }}
    </div>
</section>
