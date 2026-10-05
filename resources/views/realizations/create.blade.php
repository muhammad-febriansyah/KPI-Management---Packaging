<x-layouts.app title="Tambah Realisasi" active="realizations" :current-client="$currentClient" :user="$user">
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <div class="mb-3 flex items-center gap-2 text-xs font-medium text-slate-500"><span>Transaksi</span><x-icon name="chevron-right" size="size-3.5"/><span>Realisasi</span><x-icon name="chevron-right" size="size-3.5"/><span class="text-primary-600">Tambah</span></div>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Tambah Realisasi</h1>
            <p class="mt-2 text-sm text-slate-500">Lengkapi semua data pada tab Produk dan Assign. Foto bersifat opsional.</p>
        </div>
        <a href="{{ route('realizations.index') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-line bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"><x-icon name="chevron-right" class="rotate-180" size="size-4"/> Kembali</a>
    </div>

    <form method="POST" enctype="multipart/form-data" data-realization-form data-realization-wizard action="{{ route('realizations.store') }}" data-redirect="{{ route('realizations.index') }}" class="grid gap-5">
        <input type="hidden" name="_method" value="POST">

        <x-card>
            <div class="mb-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-primary-600">Bagian <span data-wizard-current-label>1</span> dari 3</p>
                <h2 class="mt-1 text-lg font-semibold text-slate-900" data-wizard-title tabindex="-1">Produk</h2>
                <p class="mt-1 text-sm text-slate-500" data-wizard-description>Isi data pekerjaan dan hasil produksi.</p>
            </div>
            <div class="inline-flex max-w-full gap-1 overflow-x-auto rounded-2xl bg-slate-100 p-1.5" role="tablist" aria-label="Bagian form realisasi">
                <button id="realization-tab-1" type="button" role="tab" aria-selected="true" aria-controls="realization-panel-1" data-wizard-tab="1" class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-primary-700 shadow-sm ring-1 ring-primary-100 transition-all duration-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"><span data-wizard-tab-icon class="grid size-8 place-items-center rounded-lg bg-primary-50 text-primary-600"><x-icon name="cube" size="size-4" /></span>Produk</button>
                <button id="realization-tab-2" type="button" role="tab" aria-selected="false" aria-controls="realization-panel-2" data-wizard-tab="2" class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 transition-all duration-200 hover:bg-white/70 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"><span data-wizard-tab-icon class="grid size-8 place-items-center rounded-lg text-slate-500"><x-icon name="photo" size="size-4" /></span>Foto</button>
                <button id="realization-tab-3" type="button" role="tab" aria-selected="false" aria-controls="realization-panel-3" data-wizard-tab="3" class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 transition-all duration-200 hover:bg-white/70 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"><span data-wizard-tab-icon class="grid size-8 place-items-center rounded-lg text-slate-500"><x-icon name="users" size="size-4" /></span>Assign</button>
            </div>
            <p class="mt-3 text-xs text-slate-500"><span class="font-semibold text-rose-600">*</span> Wajib diisi. Foto tidak wajib. Anda bisa berpindah tab kapan saja.</p>
        </x-card>

        <section id="realization-panel-1" data-wizard-step="1" role="tabpanel" aria-labelledby="realization-tab-1" aria-label="Produk" tabindex="0">
            <x-card>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-2 text-sm font-semibold"><span>Tanggal borongan <span class="text-rose-600" aria-hidden="true">*</span></span><input type="text" name="work_date" data-wizard-required="Tanggal borongan" aria-required="true" data-datepicker autocomplete="off" placeholder="Pilih tanggal" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Shift <span class="text-rose-600" aria-hidden="true">*</span></span><select name="shift_id" data-wizard-required="Shift" aria-required="true" data-tom-select data-tom-select-remote="{{ route('shifts.options') }}" data-tom-select-placeholder="Pilih shift..." class="h-11 rounded-lg border border-line bg-white px-3 font-normal"><option value="">Pilih shift...</option></select></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Nomor batch <span class="text-rose-600" aria-hidden="true">*</span></span><input type="text" name="batch_no" data-wizard-required="Nomor batch" aria-required="true" data-batch-number data-batch-number-url="{{ route('batches.next-number') }}" value="{{ $defaultBatchNumber }}" readonly class="h-11 rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-600"><small class="font-normal text-slate-500">Format otomatis dan increment: B-yyyymmdd-0001</small></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>SKU <span class="text-rose-600" aria-hidden="true">*</span></span><select name="product_id" data-wizard-required="SKU" aria-required="true" data-product-select data-tom-select data-tom-select-remote="{{ route('products.options') }}" data-tom-select-placeholder="Pilih SKU..." class="h-11 rounded-lg border border-line bg-white px-3 font-normal"><option value="">Pilih SKU...</option></select></label>
                    <label class="grid gap-2 text-sm font-semibold sm:col-span-2"><span>Nama produk</span><input type="text" data-product-name readonly placeholder="Otomatis terisi berdasarkan SKU" class="h-11 rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-600"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Satuan</span><input type="text" data-product-unit readonly placeholder="Otomatis terisi berdasarkan SKU" class="h-11 rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-600"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Estimasi output/jam</span><input type="text" data-product-estimate readonly placeholder="Otomatis terisi berdasarkan SKU" class="h-11 rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-600"></label>
                </div>
            </x-card>

            <x-card class="mt-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-2 text-sm font-semibold"><span>Total (Karton/Kg) <span class="text-rose-600" aria-hidden="true">*</span></span><input type="number" step="0.001" min="0" name="total_output" data-wizard-required="Total output" aria-required="true" data-total-output-input placeholder="Masukkan total output" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Total harga</span><input type="text" data-total-price-preview readonly value="Rp 0" aria-live="polite" class="h-11 rounded-lg border border-line bg-slate-50 px-3 font-semibold text-slate-700"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Waktu pengerjaan (1) <span class="text-rose-600" aria-hidden="true">*</span></span><input type="time" name="start_time" data-wizard-required="Waktu pengerjaan (1)" aria-required="true" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Waktu pengerjaan (2) <span class="text-rose-600" aria-hidden="true">*</span></span><input type="time" name="end_time" data-wizard-required="Waktu pengerjaan (2)" aria-required="true" class="h-11 rounded-lg border border-line px-3 font-normal"></label>
                    <label class="grid gap-2 text-sm font-semibold sm:col-span-2"><span>Report <span class="text-rose-600" aria-hidden="true">*</span></span><textarea name="report" data-wizard-required="Report" aria-required="true" rows="4" placeholder="Tambahkan catatan hasil pekerjaan" class="rounded-lg border border-line px-3 py-2 font-normal"></textarea></label>
                </div>
            </x-card>
        </section>

        <section id="realization-panel-2" data-wizard-step="2" role="tabpanel" aria-labelledby="realization-tab-2" aria-label="Foto" class="hidden" tabindex="0">
            <x-card>
                <div class="mb-4 flex items-start gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary-50 text-primary-600"><x-icon name="arrow-up-tray" size="size-5" /></span>
                    <div><h2 class="text-base font-semibold text-slate-900">Foto hasil pekerjaan</h2><p class="mt-1 text-sm text-slate-500">Tambahkan dokumentasi hasil pekerjaan bila diperlukan.</p></div>
                </div>
                <x-form.file-upload name="result_image" label="Upload foto (opsional)" help="JPG, PNG, atau WEBP. Maksimal 3 MB. Foto lebih besar akan dikompres otomatis." max-size-mb="3" compress />
            </x-card>

        </section>

        <section id="realization-panel-3" data-wizard-step="3" role="tabpanel" aria-labelledby="realization-tab-3" aria-label="Assign karyawan" class="hidden" tabindex="0">
            @if($user->isAdminFor($currentClient) || in_array($user->roleCodeFor($currentClient), ['leader', 'employee'], true))
                <x-card>
                    <div data-assignment-section>
                        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><div><h3 class="text-sm font-semibold text-slate-900">Assign karyawan <span class="text-rose-600" aria-hidden="true">*</span></h3><p class="mt-1 text-xs text-slate-500">Pilih minimal satu karyawan untuk menyimpan realisasi.</p></div><div class="flex flex-wrap gap-2"><select data-assignment-group data-tom-select data-tom-select-placeholder="Cari group..." class="h-10 w-80 max-w-full rounded-lg border border-line bg-white px-3 text-xs"><option value="">Tambah dari group...</option>@foreach($groups as $group)<option value="{{ $group->id }}">{{ $group->name }} ({{ $employees->where('group_id', $group->id)->count() }} karyawan)</option>@endforeach</select><button type="button" data-assignment-add-group class="inline-flex h-10 items-center gap-1.5 rounded-lg bg-emerald-50 px-3 text-xs font-semibold text-emerald-700 hover:bg-emerald-100"><x-icon name="plus" size="size-4" /> Group</button></div></div>
                        <div data-assignment-rows class="mt-3 grid gap-2"><div data-assignment-row class="flex items-center gap-2"><select name="employee_ids[]" data-assignment-employee aria-required="true" class="h-10 min-w-0 flex-1 rounded-lg border border-line bg-white px-3 text-sm"><option value="">Pilih karyawan...</option>@foreach($employees as $employee)<option value="{{ $employee->id }}" data-employee-group="{{ $employee->group_id }}">{{ $employee->employee_no }} — {{ $employee->full_name }}</option>@endforeach</select><button type="button" data-assignment-remove class="hidden rounded-lg bg-red-50 p-2 text-red-600 hover:bg-red-100" aria-label="Hapus karyawan"><x-icon name="trash" size="size-4" /></button><button type="button" data-assignment-add class="rounded-lg bg-emerald-50 p-2 text-emerald-700 hover:bg-emerald-100" aria-label="Tambah karyawan"><x-icon name="plus" size="size-4" /></button></div></div>
                    </div>
                </x-card>
            @else
                <x-card><div class="flex items-start gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-lg bg-primary-50 text-primary-600"><x-icon name="user" size="size-5" /></span><div><h3 class="text-sm font-semibold text-slate-900">Realisasi Anda</h3><p class="mt-1 text-sm text-slate-500">Data yang disimpan akan otomatis ditugaskan ke akun Anda.</p></div></div></x-card>
            @endif
        </section>

        <div class="flex flex-col-reverse justify-between gap-3 sm:flex-row">
            <a href="{{ route('realizations.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-100"><x-icon name="x-mark" size="size-4" /> Batal</a>
            <div class="flex justify-end gap-3">
                <button type="button" data-wizard-back class="hidden items-center gap-2 rounded-lg border border-line bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"><x-icon name="chevron-right" class="rotate-180" size="size-4" /> Sebelumnya</button>
                <button type="button" data-wizard-next class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">Berikutnya <x-icon name="chevron-right" size="size-4" /></button>
                <button type="submit" data-wizard-submit class="hidden items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700"><x-icon name="check-circle" size="size-4" /> Simpan realisasi</button>
            </div>
        </div>
    </form>
</x-layouts.app>
