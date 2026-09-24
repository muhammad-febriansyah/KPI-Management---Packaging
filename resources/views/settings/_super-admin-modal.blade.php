<div data-super-admin-modal class="fixed inset-0 z-[90] hidden place-items-center bg-slate-950/40 p-4" role="dialog" aria-modal="true" aria-labelledby="super-admin-modal-title">
    <div class="w-full max-w-xl rounded-xl bg-white p-6 shadow-xl">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-primary-600">Akun khusus</p>
                <h2 id="super-admin-modal-title" data-super-admin-modal-title class="mt-1 text-lg font-semibold text-slate-950">Tambah Super Admin</h2>
                <p class="mt-1 text-sm text-slate-500">Super Admin memiliki akses penuh ke seluruh menu.</p>
            </div>
            <button type="button" data-super-admin-close class="grid size-9 shrink-0 place-items-center rounded-lg text-slate-500 hover:bg-slate-50" aria-label="Tutup"><x-icon name="x-mark" /></button>
        </div>
        <form data-super-admin-form class="grid gap-4">
            <input type="hidden" name="_method" value="POST">
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-2 text-sm font-semibold sm:col-span-2"><span>Nama lengkap <span class="text-danger">*</span></span><input name="name" required autocomplete="name" class="h-11 rounded-lg border border-line px-3 font-normal" placeholder="Nama Super Admin"></label>
                <label class="grid gap-2 text-sm font-semibold"><span>Username <span class="text-danger">*</span></span><input name="username" required autocomplete="username" class="h-11 rounded-lg border border-line px-3 font-normal" placeholder="superadmin"></label>
                <label class="grid gap-2 text-sm font-semibold"><span>Email <span class="text-danger">*</span></span><input type="email" name="email" required autocomplete="email" class="h-11 rounded-lg border border-line px-3 font-normal" placeholder="admin@perusahaan.com"></label>
                <label class="grid gap-2 text-sm font-semibold"><span>Status <span class="text-danger">*</span></span><select name="status" required class="h-11 rounded-lg border border-line bg-white px-3 font-normal"><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select></label>
                <div class="hidden sm:block"></div>
                <label class="grid gap-2 text-sm font-semibold"><span>Password <span class="text-danger" data-password-required-mark>*</span></span><x-form.password-input name="password" data-required-on-create /></label>
                <label class="grid gap-2 text-sm font-semibold"><span>Konfirmasi password <span class="text-danger" data-password-required-mark>*</span></span><x-form.password-input name="password_confirmation" placeholder="Ulangi password" data-required-on-create /></label>
            </div>
            <div class="flex justify-end gap-3"><button type="button" data-super-admin-close class="rounded-lg border border-line px-4 py-2.5 text-sm font-semibold">Batal</button><button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white"><x-icon name="check-circle" size="size-4" /> Simpan</button></div>
        </form>
    </div>
</div>
