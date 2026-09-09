@props([
    'title' => 'Belum ada data untuk ditampilkan.',
    'description' => 'Tambahkan data pertama untuk memulai.',
])

<div {{ $attributes->class(['flex min-h-52 flex-col items-center justify-center px-6 py-10 text-center']) }}>
    <span class="mb-4 grid size-11 place-items-center rounded-lg border border-line bg-slate-50 text-slate-400">
        <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
            <path d="M5 7.5h14M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" stroke-linecap="round" />
            <path d="M9 12h6M9 15.5h4" stroke-linecap="round" />
        </svg>
    </span>
    <h3 class="text-sm font-semibold text-ink">{{ $title }}</h3>
    <p class="mt-1 max-w-sm text-xs leading-5 text-muted">{{ $description }}</p>

    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
