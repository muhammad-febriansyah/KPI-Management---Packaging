@props([
    'editRoute' => null,
    'deleteRoute',
    'editLabel' => 'Edit',
    'deleteLabel' => 'Hapus',
    'modalEditUrl' => null,
    'modalUpdateUrl' => null,
])

<div class="flex justify-end gap-2">
    @if ($modalEditUrl && $modalUpdateUrl)
        <button
            type="button"
            data-unit-edit
            data-edit-url="{{ $modalEditUrl }}"
            data-update-url="{{ $modalUpdateUrl }}"
            class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"
        >
            <x-icon name="pencil" size="size-4" /> {{ $editLabel }}
        </button>
    @else
        <a href="{{ $editRoute }}" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">
            <x-icon name="pencil" size="size-4" /> {{ $editLabel }}
        </a>
    @endif
    <form method="POST" action="{{ $deleteRoute }}" data-ajax-delete>
        @csrf
        @method('DELETE')
        <button type="submit" class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100">
            <x-icon name="trash" size="size-4" /> {{ $deleteLabel }}
        </button>
    </form>
</div>
