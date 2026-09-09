<x-layouts.app title="Master Satuan" active="units" :current-client="$currentClient" :user="$user">
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div><p class="text-xs text-slate-500">Master Data / Satuan</p><h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">Master Satuan</h1><p class="mt-1 text-sm text-slate-500">Kelola satuan output produk untuk client aktif.</p></div>
        <button type="button" data-unit-create class="inline-flex h-11 cursor-pointer items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white hover:bg-primary-700"><x-icon name="plus" size="size-4" /> Tambah satuan</button>
    </div>
    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table
                data-server-table
                data-search-placeholder="Cari kode atau nama satuan"
                data-empty-message="Belum ada satuan pada client ini."
                class="w-full min-w-[560px] text-left text-sm"
            >
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Kode</th>
                        <th class="px-5 py-3">Nama satuan</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line"></tbody>
            </table>
        </div>
    </x-card>

    <div data-unit-modal class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/40 p-4">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
            <div class="mb-5 flex items-center justify-between">
                <h2 data-unit-modal-title class="text-lg font-semibold text-slate-950">Tambah satuan</h2>
                <button type="button" data-unit-modal-close class="grid size-9 cursor-pointer place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-ink">
                    <x-icon name="x-mark" size="size-5" />
                </button>
            </div>

            <form data-unit-form action="{{ route('units.store') }}" class="grid gap-5">
                @csrf
                <div class="grid gap-5 sm:grid-cols-2">
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        <span>Kode satuan <span class="text-danger">*</span></span>
                        <input name="code" required maxlength="30" placeholder="Contoh: PCS" class="h-11 rounded-lg border border-line px-3 font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100">
                        <span data-error-for="code" class="hidden text-xs font-normal text-danger"></span>
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        <span>Nama satuan <span class="text-danger">*</span></span>
                        <input name="name" required maxlength="50" placeholder="Contoh: Pieces" class="h-11 rounded-lg border border-line px-3 font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100">
                        <span data-error-for="name" class="hidden text-xs font-normal text-danger"></span>
                    </label>
                </div>
                <label class="grid max-w-sm gap-2 text-sm font-semibold text-slate-700">
                    <span>Status <span class="text-danger">*</span></span>
                    <select name="status" required class="h-11 rounded-lg border border-line bg-white px-3 font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100">
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                    <span data-error-for="status" class="hidden text-xs font-normal text-danger"></span>
                </label>
                <div class="flex justify-end gap-3 border-t border-line pt-5">
                    <button type="button" data-unit-modal-close class="cursor-pointer rounded-lg border border-line px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                    <button type="submit" data-unit-submit class="inline-flex min-w-36 cursor-pointer items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700 disabled:cursor-wait disabled:opacity-70">
                        <span data-unit-submit-label>Simpan satuan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
