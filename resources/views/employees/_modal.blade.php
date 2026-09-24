<div data-employee-modal class="fixed inset-0 z-[90] hidden place-items-center bg-slate-950/40 p-4">
    <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-6 shadow-xl">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h2 data-employee-modal-title class="text-lg font-semibold">Tambah karyawan</h2>
                <p class="mt-1 text-xs text-slate-500">Akun login dibuat otomatis menggunakan ID karyawan. Password dapat diatur dari menu Akses.</p>
            </div>
            <button type="button" data-employee-close aria-label="Tutup"><x-icon name="x-mark" /></button>
        </div>
        <form data-employee-form class="grid gap-4">
            <input type="hidden" name="_method" value="POST">
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-2 text-sm font-semibold"><span>ID Karyawan <span class="text-danger">*</span></span><input name="employee_no" readonly placeholder="Dibuat otomatis oleh sistem" class="h-11 rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-600"></label>
                <label class="grid gap-2 text-sm font-semibold"><span>SIM ID</span><input name="sim_id" placeholder="Contoh: PEG1234" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
                <label class="grid gap-2 text-sm font-semibold"><span>Nama lengkap <span class="text-danger">*</span></span><input name="full_name" required placeholder="Masukkan nama lengkap" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
                <label class="grid gap-2 text-sm font-semibold"><span>Nomor telepon <span class="text-danger">*</span></span><input name="phone" required placeholder="Contoh: 081234567890" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
                <label class="grid gap-2 text-sm font-semibold"><span>Tanggal masuk <span class="text-danger">*</span></span><input type="text" name="join_date" data-datepicker required autocomplete="off" placeholder="Pilih tanggal" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
                <label class="grid gap-2 text-sm font-semibold"><span>Jenis kelamin <span class="text-danger">*</span></span><select name="gender" required class="h-11 rounded-lg border border-line bg-white px-3 font-normal"><option value="male">Laki-laki</option><option value="female">Perempuan</option></select></label>
                <label class="grid gap-2 text-sm font-semibold"><span>Status karyawan <span class="text-danger">*</span></span><select name="employee_status" required class="h-11 rounded-lg border border-line bg-white px-3 font-normal"><option value="permanent">Tetap</option><option value="contract">Kontrak</option><option value="daily">Harian</option></select></label>
                <label class="grid gap-2 text-sm font-semibold"><span>Status perkawinan <span class="text-danger">*</span></span><select name="marital_status" required class="h-11 rounded-lg border border-line bg-white px-3 font-normal"><option value="single">Belum menikah</option><option value="married">Menikah</option><option value="divorced">Cerai hidup</option><option value="widowed">Cerai mati</option></select></label>
                <label class="grid gap-2 text-sm font-semibold"><span>Group</span><select name="group_id" data-tom-select class="h-11 rounded-lg border border-line bg-white px-3 font-normal"><option value="">Tanpa group</option>@foreach ($groups as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></label>
            </div>
            <div class="flex justify-end gap-3"><button type="button" data-employee-close class="rounded-lg border border-line px-4 py-2.5 text-sm font-semibold">Batal</button><button class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white">Simpan</button></div>
        </form>
    </div>
</div>
