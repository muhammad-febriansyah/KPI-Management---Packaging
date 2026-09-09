@props([
    'name',
    'label',
    'accept' => 'image/png,image/jpeg,image/webp',
    'help' => 'PNG, JPG, atau WEBP. Maksimal 2 MB.',
])

<div>
    <span class="mb-1.5 block text-sm font-medium text-slate-700">{{ $label }}</span>
    <label
        data-file-upload
        for="{{ $attributes->get('id', $name) }}"
        class="flex min-h-36 cursor-pointer items-center gap-4 rounded-lg border border-dashed border-slate-300 bg-white p-4 transition-colors hover:border-primary-600 hover:bg-primary-50 focus-within:border-primary-600 focus-within:ring-3 focus-within:ring-primary-100"
    >
        <span class="grid size-11 shrink-0 place-items-center rounded-lg border border-line bg-slate-50 text-slate-500">
            <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                <path d="M12 16V4m0 0L8 8m4-4 4 4M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>

        <span class="min-w-0 flex-1">
            <span data-file-name class="block truncate text-sm font-semibold text-ink">Pilih file atau seret ke area ini</span>
            <span data-file-meta class="mt-1 block text-xs text-muted">{{ $help }}</span>
        </span>

        <img data-file-preview class="hidden size-16 rounded-lg border border-line object-cover" alt="Preview file yang dipilih">
        <input id="{{ $attributes->get('id', $name) }}" name="{{ $name }}" type="file" accept="{{ $accept }}" class="sr-only">
    </label>

    @error($name)
        <p class="mt-1.5 text-xs text-danger">{{ $message }}</p>
    @enderror
</div>
