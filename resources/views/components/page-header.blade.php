@props([
    'breadcrumb',
    'title',
    'description',
])

<header {{ $attributes->class(['mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0">
        <p class="mb-2 text-xs font-medium text-muted">{{ $breadcrumb }}</p>
        <h1 class="text-2xl font-semibold tracking-[-0.025em] text-ink">{{ $title }}</h1>
        <p class="mt-1.5 max-w-3xl text-sm leading-6 text-muted">{{ $description }}</p>
    </div>

    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</header>
