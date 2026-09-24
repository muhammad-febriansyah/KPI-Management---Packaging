<div data-user-create-modal class="fixed inset-0 z-[85] hidden place-items-center bg-slate-950/40 p-4" role="dialog" aria-modal="true" aria-labelledby="user-create-modal-title">
    <div class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-6 shadow-xl">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-primary-600">User baru</p>
                <h2 id="user-create-modal-title" class="mt-1 text-lg font-semibold text-slate-950">Tambah User</h2>
                <p class="mt-1 text-sm text-slate-500">Pilih role, lalu lengkapi data sesuai kebutuhan role tersebut.</p>
            </div>
            <button type="button" data-user-create-close class="grid size-9 shrink-0 place-items-center rounded-lg text-slate-500 hover:bg-slate-50" aria-label="Tutup"><x-icon name="x-mark" /></button>
        </div>
        <form data-user-create-form class="grid gap-5">
            <input type="hidden" name="_method" value="POST">
            <fieldset>
                <legend class="mb-3 text-sm font-semibold text-slate-900">Role user</legend>
                <div class="grid gap-3 sm:grid-cols-3">
                    <label class="relative cursor-pointer"><input type="radio" name="create_role" value="super-admin" class="peer sr-only" checked><span class="flex min-h-14 items-center gap-2 rounded-xl border border-line px-3 text-sm font-semibold text-slate-600 transition peer-checked:border-indigo-400 peer-checked:bg-indigo-50 peer-checked:text-indigo-800 peer-focus-visible:outline-2 peer-focus-visible:outline-indigo-600"><x-icon name="shield-check" size="size-5" /> Super Admin</span></label>
                    <label class="relative cursor-pointer"><input type="radio" name="create_role" value="employee" class="peer sr-only"><span class="flex min-h-14 items-center gap-2 rounded-xl border border-line px-3 text-sm font-semibold text-slate-600 transition peer-checked:border-sky-400 peer-checked:bg-sky-50 peer-checked:text-sky-800 peer-focus-visible:outline-2 peer-focus-visible:outline-sky-600"><x-icon name="users" size="size-5" /> Karyawan</span></label>
                    <label class="relative cursor-pointer"><input type="radio" name="create_role" value="client" class="peer sr-only"><span class="flex min-h-14 items-center gap-2 rounded-xl border border-line px-3 text-sm font-semibold text-slate-600 transition peer-checked:border-emerald-400 peer-checked:bg-emerald-50 peer-checked:text-emerald-800 peer-focus-visible:outline-2 peer-focus-visible:outline-emerald-600"><x-icon name="building-office-2" size="size-5" /> Client</span></label>
                </div>
            </fieldset>

            <section data-user-create-section="super-admin" class="grid gap-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-2 text-sm font-semibold sm:col-span-2"><span>Nama lengkap <span class="text-danger">*</span></span><input name="name" required autocomplete="name" class="h-11 rounded-lg border border-line px-3 font-normal" placeholder="Nama Super Admin"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Username <span class="text-danger">*</span></span><input name="username" required autocomplete="username" class="h-11 rounded-lg border border-line px-3 font-normal" placeholder="superadmin"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Email <span class="text-danger">*</span></span><input type="email" name="email" required autocomplete="email" class="h-11 rounded-lg border border-line px-3 font-normal" placeholder="admin@perusahaan.com"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Status <span class="text-danger">*</span></span><select name="status" required class="h-11 rounded-lg border border-line bg-white px-3 font-normal"><option value="active" selected>Aktif</option><option value="inactive">Nonaktif</option></select></label>
                    <div class="hidden sm:block"></div>
                    <label class="grid gap-2 text-sm font-semibold"><span>Password <span class="text-danger">*</span></span><input type="password" name="password" required minlength="8" autocomplete="new-password" class="h-11 rounded-lg border border-line px-3 font-normal" placeholder="Minimal 8 karakter"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Konfirmasi password <span class="text-danger">*</span></span><input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="h-11 rounded-lg border border-line px-3 font-normal" placeholder="Ulangi password"></label>
                </div>
            </section>

            <section data-user-create-section="employee" class="hidden grid gap-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-2 text-sm font-semibold"><span>SIM ID</span><input name="sim_id" placeholder="Contoh: PEG1234" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Nama lengkap <span class="text-danger">*</span></span><input name="full_name" required placeholder="Masukkan nama lengkap" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Nomor telepon <span class="text-danger">*</span></span><input name="phone" required placeholder="Contoh: 081234567890" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Tanggal masuk <span class="text-danger">*</span></span><input type="text" name="join_date" data-datepicker required autocomplete="off" placeholder="Pilih tanggal" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Jenis kelamin <span class="text-danger">*</span></span><select name="gender" required class="h-11 rounded-lg border border-line bg-white px-3 font-normal"><option value="male">Laki-laki</option><option value="female">Perempuan</option></select></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Status karyawan <span class="text-danger">*</span></span><select name="employee_status" required class="h-11 rounded-lg border border-line bg-white px-3 font-normal"><option value="permanent">Tetap</option><option value="contract">Kontrak</option><option value="daily">Harian</option></select></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Status perkawinan <span class="text-danger">*</span></span><select name="marital_status" required class="h-11 rounded-lg border border-line bg-white px-3 font-normal"><option value="single">Belum menikah</option><option value="married">Menikah</option><option value="divorced">Cerai hidup</option><option value="widowed">Cerai mati</option></select></label>
                    <label class="grid gap-2 text-sm font-semibold sm:col-span-2"><span>Group</span><select name="group_id" data-tom-select class="h-11 rounded-lg border border-line bg-white px-3 font-normal"><option value="">Tanpa group</option>@foreach ($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></label>
                </div>
            </section>

            <section data-user-create-section="client" class="hidden grid gap-4">
                <input type="hidden" name="provision_account" value="1">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-2 text-sm font-semibold"><span>Kode client <span class="text-danger">*</span></span><input name="code" required placeholder="Contoh: KP-001" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Nama client <span class="text-danger">*</span></span><input name="name" required placeholder="Masukkan nama perusahaan" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Status <span class="text-danger">*</span></span><select name="status" required class="h-11 rounded-lg border border-line bg-white px-3 font-normal"><option value="active" selected>Aktif</option><option value="inactive">Nonaktif</option></select></label>
                </div>
                <div class="grid gap-4 rounded-xl border border-line bg-slate-50/70 p-4 sm:grid-cols-2">
                    <label class="grid gap-2 text-sm font-semibold sm:col-span-2"><span>Nama PIC <span class="text-danger">*</span></span><input name="account_name" required placeholder="Nama penanggung jawab" autocomplete="name" class="h-11 rounded-lg border border-line bg-white px-3 font-normal"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Username <span class="text-danger">*</span></span><input name="login_username" required placeholder="Contoh: pic.client" autocomplete="username" class="h-11 rounded-lg border border-line bg-white px-3 font-normal"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Email login <span class="text-danger">*</span></span><input type="email" name="login_email" required placeholder="pic@perusahaan.com" autocomplete="email" class="h-11 rounded-lg border border-line bg-white px-3 font-normal"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Password <span class="text-danger">*</span></span><input type="password" name="password" required minlength="8" autocomplete="new-password" class="h-11 rounded-lg border border-line bg-white px-3 font-normal" placeholder="Minimal 8 karakter"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Konfirmasi password <span class="text-danger">*</span></span><input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="h-11 rounded-lg border border-line bg-white px-3 font-normal" placeholder="Ulangi password"></label>
                </div>
            </section>

            <div class="flex justify-end gap-3"><button type="button" data-user-create-close class="rounded-lg border border-line px-4 py-2.5 text-sm font-semibold">Batal</button><button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white"><x-icon name="check-circle" size="size-4" /> Simpan</button></div>
        </form>
    </div>
</div>
