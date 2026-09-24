<div data-access-role-picker class="fixed inset-0 z-[85] hidden place-items-center bg-slate-950/40 p-4" role="dialog" aria-modal="true" aria-labelledby="access-role-picker-title">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-primary-600">User baru</p>
                <h2 id="access-role-picker-title" class="mt-1 text-lg font-semibold text-slate-950">Pilih role user</h2>
                <p class="mt-1 text-sm text-slate-500">Form berikutnya akan menyesuaikan role yang dipilih.</p>
            </div>
            <button type="button" data-access-role-picker-close class="grid size-9 shrink-0 place-items-center rounded-lg text-slate-500 hover:bg-slate-50" aria-label="Tutup"><x-icon name="x-mark" /></button>
        </div>
        <div class="grid gap-3">
            <button type="button" data-access-role-option data-super-admin-create class="flex min-h-16 items-center gap-3 rounded-xl border border-indigo-200 bg-indigo-50 px-4 text-left text-indigo-800 transition hover:border-indigo-300 hover:bg-indigo-100 focus-visible:outline-2 focus-visible:outline-indigo-600"><span class="grid size-10 shrink-0 place-items-center rounded-lg bg-white text-indigo-600 shadow-sm"><x-icon name="shield-check" /></span><span><span class="block text-sm font-semibold">Super Admin</span><span class="mt-0.5 block text-xs text-indigo-700/80">Akses penuh dan pengaturan sistem</span></span></button>
            <button type="button" data-access-role-option data-employee-create class="flex min-h-16 items-center gap-3 rounded-xl border border-sky-200 bg-sky-50 px-4 text-left text-sky-800 transition hover:border-sky-300 hover:bg-sky-100 focus-visible:outline-2 focus-visible:outline-sky-600"><span class="grid size-10 shrink-0 place-items-center rounded-lg bg-white text-sky-600 shadow-sm"><x-icon name="users" /></span><span><span class="block text-sm font-semibold">Karyawan</span><span class="mt-0.5 block text-xs text-sky-700/80">Data karyawan dan akun login</span></span></button>
            <button type="button" data-access-role-option data-client-create class="flex min-h-16 items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-left text-emerald-800 transition hover:border-emerald-300 hover:bg-emerald-100 focus-visible:outline-2 focus-visible:outline-emerald-600"><span class="grid size-10 shrink-0 place-items-center rounded-lg bg-white text-emerald-600 shadow-sm"><x-icon name="building-office-2" /></span><span><span class="block text-sm font-semibold">Client</span><span class="mt-0.5 block text-xs text-emerald-700/80">Client dan akun PIC login</span></span></button>
        </div>
        <div class="mt-5 flex justify-end"><button type="button" data-access-role-picker-close class="rounded-lg border border-line px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button></div>
    </div>
</div>
