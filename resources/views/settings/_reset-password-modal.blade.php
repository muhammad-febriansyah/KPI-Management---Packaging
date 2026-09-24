<div data-user-reset-modal class="fixed inset-0 z-[95] hidden place-items-center bg-slate-950/40 p-4" role="dialog" aria-modal="true" aria-labelledby="user-reset-modal-title">
    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-primary-600">Keamanan akun</p>
                <h2 id="user-reset-modal-title" class="mt-1 text-lg font-semibold text-slate-950">Reset password</h2>
                <p class="mt-1 text-sm text-slate-500">Password baru untuk <span data-user-reset-name class="font-semibold text-slate-700">user</span>.</p>
            </div>
            <button type="button" data-user-reset-close class="grid size-9 shrink-0 place-items-center rounded-lg text-slate-500 hover:bg-slate-50" aria-label="Tutup"><x-icon name="x-mark" /></button>
        </div>
        <form data-user-reset-form class="grid gap-4">
            <input type="hidden" name="_method" value="PUT">
            <label class="grid gap-2 text-sm font-semibold"><span>Password baru <span class="text-danger">*</span></span><x-form.password-input name="password" required /></label>
            <label class="grid gap-2 text-sm font-semibold"><span>Konfirmasi password <span class="text-danger">*</span></span><x-form.password-input name="password_confirmation" placeholder="Ulangi password baru" required /></label>
            <div class="flex justify-end gap-3"><button type="button" data-user-reset-close class="rounded-lg border border-line px-4 py-2.5 text-sm font-semibold">Batal</button><button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white"><x-icon name="key" size="size-4" /> Simpan password</button></div>
        </form>
    </div>
</div>
