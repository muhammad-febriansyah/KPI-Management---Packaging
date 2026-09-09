<x-layouts.app title="Master Group" active="groups" :current-client="$currentClient" :user="$user">
<div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="text-xs text-slate-500">Master Data / Group</p><h1 class="mt-2 text-2xl font-semibold text-slate-950">Master Group</h1><p class="mt-1 text-sm text-slate-500">Kelola pengelompokan karyawan dan produk.</p></div><button type="button" data-group-create class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white hover:bg-primary-700"><x-icon name="plus" size="size-4" /> Tambah group</button></div>
<x-card :padding="false">
    <div class="overflow-x-auto">
        <table
            data-server-table
            data-search-placeholder="Cari kode atau nama group"
            data-empty-message="Belum ada group pada client ini."
            class="w-full min-w-[560px] text-left text-sm"
        >
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3">Kode</th>
                    <th class="px-5 py-3">Nama group</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line"></tbody>
        </table>
    </div>
</x-card>
<div data-group-modal class="fixed inset-0 z-[90] hidden place-items-center bg-slate-950/40 p-4"><div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl"><div class="mb-5 flex items-center justify-between"><h2 data-group-modal-title class="text-lg font-semibold">Tambah group</h2><button type="button" data-group-close><x-icon name="x-mark"/></button></div><form data-group-form class="grid gap-4"><input type="hidden" name="_method" value="POST"><label class="grid gap-2 text-sm font-semibold">Kode<input name="code" required class="h-11 rounded-lg border border-line px-3 font-normal"></label><label class="grid gap-2 text-sm font-semibold">Nama group<input name="name" required class="h-11 rounded-lg border border-line px-3 font-normal"></label><label class="grid gap-2 text-sm font-semibold">Status<select name="status" class="h-11 rounded-lg border border-line bg-white px-3 font-normal"><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select></label><div class="flex justify-end gap-3"><button type="button" data-group-close class="rounded-lg border border-line px-4 py-2.5 text-sm font-semibold">Batal</button><button class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white">Simpan</button></div></form></div></div>
</x-layouts.app>
