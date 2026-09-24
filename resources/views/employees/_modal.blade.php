<div data-employee-modal class="fixed inset-0 z-[90] hidden place-items-center bg-slate-950/40 p-4">
    <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-6 shadow-xl">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h2 data-employee-modal-title class="text-lg font-semibold">Tambah karyawan</h2>
                <p class="mt-1 text-xs text-slate-500">Akun login dibuat otomatis. Password awal dapat menggunakan nilai default <span class="font-semibold">password</span>.</p>
            </div>
            <button type="button" data-employee-close aria-label="Tutup"><x-icon name="x-mark" /></button>
        </div>
        <form data-employee-form class="grid gap-4">
            <input type="hidden" name="_method" value="POST">
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-2 text-sm font-semibold"><span>ID Karyawan <span class="text-danger">*</span></span><input name="employee_no" readonly placeholder="Dibuat otomatis oleh sistem" class="h-11 rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-600"></label>
                <label class="grid gap-2 text-sm font-semibold"><span>SIM ID</span><input name="sim_id" placeholder="Contoh: PEG1234" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
                <label class="grid gap-2 text-sm font-semibold"><span>Nama lengkap <span class="text-danger">*</span></span><input name="full_name" required placeholder="Masukkan nama lengkap" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
                <label class="grid gap-2 text-sm font-semibold"><span>Email karyawan</span><input type="email" name="email" placeholder="Contoh: nama@perusahaan.com" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
                <label class="grid gap-2 text-sm font-semibold"><span>Nomor telepon <span class="text-danger">*</span></span><input name="phone" required placeholder="Contoh: 081234567890" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
                <label class="grid gap-2 text-sm font-semibold"><span>Tanggal masuk <span class="text-danger">*</span></span><input type="text" name="join_date" data-datepicker required autocomplete="off" placeholder="Pilih tanggal" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
                <label class="grid gap-2 text-sm font-semibold"><span>Jenis kelamin <span class="text-danger">*</span></span><select name="gender" required class="h-11 rounded-lg border border-line bg-white px-3 font-normal"><option value="male">Laki-laki</option><option value="female">Perempuan</option></select></label>
                <label class="grid gap-2 text-sm font-semibold"><span>Status karyawan <span class="text-danger">*</span></span><select name="employee_status" required class="h-11 rounded-lg border border-line bg-white px-3 font-normal"><option value="permanent">Tetap</option><option value="contract">Kontrak</option><option value="daily">Harian</option></select></label>
                <label class="grid gap-2 text-sm font-semibold"><span>Status perkawinan <span class="text-danger">*</span></span><select name="marital_status" required class="h-11 rounded-lg border border-line bg-white px-3 font-normal"><option value="single">Belum menikah</option><option value="married">Menikah</option><option value="divorced">Cerai hidup</option><option value="widowed">Cerai mati</option></select></label>
                <label class="grid gap-2 text-sm font-semibold"><span>Group</span><select name="group_id" data-tom-select class="h-11 rounded-lg border border-line bg-white px-3 font-normal"><option value="">Tanpa group</option>@foreach ($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></label>
                <label class="grid gap-2 text-sm font-semibold sm:col-span-2"><span class="flex items-center justify-between gap-3"><span>Password akun</span><button type="button" data-default-password="password" class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100">Gunakan default</button></span><div class="relative"><input type="password" name="password" autocomplete="new-password" placeholder="Masukkan password akun" class="h-11 w-full rounded-lg border border-line px-3 pr-12 font-normal"><button type="button" data-password-toggle class="absolute right-1.5 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-2 focus-visible:outline-primary-600" aria-label="Tampilkan password"><span data-password-show><x-icon name="eye" /></span><span data-password-hide class="hidden"><x-icon name="eye-slash" /></span></button></div></label>
            </div>
            <div class="flex justify-end gap-3"><button type="button" data-employee-close class="rounded-lg border border-line px-4 py-2.5 text-sm font-semibold">Batal</button><button class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white">Simpan</button></div>
        </form>
    </div>
</div>
