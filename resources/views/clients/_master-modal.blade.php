<div data-client-master-modal class="fixed inset-0 z-[90] hidden place-items-center bg-slate-950/40 p-4" role="dialog" aria-modal="true" aria-labelledby="client-master-modal-title">
    <div class="w-full max-w-xl rounded-xl bg-white p-6 shadow-xl">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-primary-600">Master Data</p>
                <h2 id="client-master-modal-title" data-client-master-modal-title class="mt-1 text-lg font-semibold text-slate-950">Tambah client</h2>
                <p class="mt-1 text-sm text-slate-500">Kelola identitas client. Akun PIC dikelola dari menu User & Hak Akses.</p>
            </div>
            <button type="button" data-client-master-close class="grid size-9 shrink-0 place-items-center rounded-lg text-slate-500 hover:bg-slate-50" aria-label="Tutup"><x-icon name="x-mark" /></button>
        </div>
        <form data-client-master-form class="grid gap-4">
            <input type="hidden" name="_method" value="POST">
            <label class="grid gap-2 text-sm font-semibold"><span>Kode client <span class="text-danger">*</span></span><input name="code" required placeholder="Contoh: KP-001" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
            <label class="grid gap-2 text-sm font-semibold"><span>Nama client <span class="text-danger">*</span></span><input name="name" required placeholder="Masukkan nama perusahaan" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
            <label class="grid gap-2 text-sm font-semibold"><span>Status <span class="text-danger">*</span></span><select name="status" required class="h-11 rounded-lg border border-line bg-white px-3 font-normal"><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select></label>
            <div class="flex justify-end gap-3"><button type="button" data-client-master-close class="rounded-lg border border-line px-4 py-2.5 text-sm font-semibold">Batal</button><button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white"><x-icon name="check-circle" size="size-4" /> Simpan</button></div>
        </form>
    </div>
</div>
