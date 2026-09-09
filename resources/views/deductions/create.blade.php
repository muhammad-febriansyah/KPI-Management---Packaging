<x-layouts.app title="Tambah Potongan Gaji" active="deductions" :current-client="$currentClient" :user="$user">
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <div class="mb-3 flex items-center gap-2 text-xs font-medium text-slate-500">
                <span>Penggajian</span>
                <x-icon name="chevron-right" size="size-3.5" />
                <span>Potongan Gaji</span>
                <x-icon name="chevron-right" size="size-3.5" />
                <span class="text-primary-600">Tambah</span>
            </div>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Tambah Potongan Gaji</h1>
            <p class="mt-2 text-sm text-slate-500">Tentukan periode, komponen potongan, dan karyawan yang dikenakan.</p>
        </div>
        <a href="{{ route('deductions.index') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-line bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
            <x-icon name="chevron-right" class="rotate-180" size="size-4" /> Kembali
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-semibold">Periksa kembali data yang diisi.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form data-deduction-create-form action="{{ route('deductions.store') }}" data-redirect="{{ route('deductions.index') }}" method="POST" class="grid gap-5">
        @csrf
        <x-card>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-2 text-sm font-semibold">
                    <span>Bulan <span class="text-danger">*</span></span>
                    <input type="month" name="month" required value="{{ old('month', now()->format('Y-m')) }}" class="h-11 rounded-lg border border-line px-3 font-normal">
                </label>
                <label class="grid gap-2 text-sm font-semibold">
                    <span>Minggu</span>
                    <select name="week_no" class="h-11 rounded-lg border border-line bg-white px-3 font-normal">
                        <option value="">Semua minggu</option>
                        @foreach ([1, 2, 3, 4, 5] as $week)
                            <option value="{{ $week }}" @selected((string) old('week_no') === (string) $week)>Minggu {{ $week }}</option>
                        @endforeach
                    </select>
                </label>

                @foreach ([['uniform_amount', 'Potongan seragam (Kaos/Celana)'], ['equipment_amount', 'Potongan perlengkapan kerja'], ['meal_amount', 'Potongan uang makan'], ['correction_minus', 'Koreksi pengurangan'], ['correction_plus', 'Koreksi penambahan']] as [$field, $label])
                    <label class="grid gap-2 text-sm font-semibold">
                        <span>{{ $label }}</span>
                        <x-rupiah-input :name="$field" :value="old($field)" placeholder="0" />
                    </label>
                @endforeach

                <label class="grid gap-2 text-sm font-semibold">
                    <span>BPJS Kesehatan (%)</span>
                    <input type="number" name="bpjs_health_percent" min="0" max="100" step="0.001" value="{{ old('bpjs_health_percent') }}" placeholder="Contoh: 1.000" class="h-11 rounded-lg border border-line px-3 font-normal">
                </label>
                <label class="grid gap-2 text-sm font-semibold">
                    <span>BPJS Ketenagakerjaan (%)</span>
                    <input type="number" name="bpjs_employment_percent" min="0" max="100" step="0.001" value="{{ old('bpjs_employment_percent') }}" placeholder="Contoh: 2.000" class="h-11 rounded-lg border border-line px-3 font-normal">
                </label>
                <label class="grid gap-2 text-sm font-semibold">
                    <span>Potongan DP Gaji</span>
                    <select name="salary_advance_type" class="h-11 rounded-lg border border-line bg-white px-3 font-normal">
                        <option value="">Tidak ada</option>
                        <option value="fixed" @selected(old('salary_advance_type') === 'fixed')>Fixed (Rupiah)</option>
                        <option value="percentage" @selected(old('salary_advance_type') === 'percentage')>Persentase gaji borongan</option>
                    </select>
                </label>
                <label class="grid gap-2 text-sm font-semibold">
                    <span>Nilai DP Gaji</span>
                    <x-rupiah-input name="salary_advance_value" :value="old('salary_advance_value')" :decimals="3" placeholder="Masukkan nilai DP" />
                </label>
            </div>
        </x-card>

        <x-card>
            <fieldset>
                <legend class="text-sm font-semibold text-slate-900">Karyawan dipilih</legend>
                <div class="mt-3 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" data-deduction-select-all class="size-4 rounded border-line text-primary-600">
                        Pilih semua karyawan
                    </label>
                    <span class="text-xs text-slate-500">{{ $employees->count() }} karyawan aktif</span>
                </div>
                <div class="mt-3 grid max-h-72 gap-2 overflow-y-auto sm:grid-cols-2">
                    @foreach ($employees as $employee)
                        <label class="flex items-center gap-2 rounded-lg border border-transparent px-2 py-2 text-sm hover:border-line hover:bg-slate-50">
                            <input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}" @checked(in_array($employee->id, old('employee_ids', []))) class="size-4 rounded border-line text-primary-600" data-deduction-employee>
                            <span class="font-medium text-slate-800">{{ $employee->full_name }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        </x-card>

        <div class="flex justify-end gap-3">
            <a href="{{ route('deductions.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-100">
                <x-icon name="x-mark" size="size-4" /> Batal
            </a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
                <x-icon name="check-circle" size="size-4" /> Simpan
            </button>
        </div>
    </form>
</x-layouts.app>
