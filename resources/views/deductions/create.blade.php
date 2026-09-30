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
                <label class="grid gap-2 text-sm font-semibold sm:col-span-2">
                    <span>Client <span class="text-danger">*</span></span>
                    @if ($user->is_super_admin)
                    <select name="client_id" data-deduction-client data-select2-select data-select2-remote="{{ route('clients.options') }}" data-select2-placeholder="Pilih client..." required class="w-full">
                                <option value="">Pilih client...</option>
                        </select>
                    @else
                        <div class="flex h-11 items-center rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-600">{{ $currentClient->code }} — {{ $currentClient->name }}</div>
                        <input type="hidden" name="client_id" data-deduction-client value="{{ $currentClient->getKey() }}">
                    @endif
                </label>
                <label class="grid gap-2 text-sm font-semibold">
                    <span>Bulan <span class="text-danger">*</span></span>
                    <input type="month" name="month" data-datepicker required value="{{ old('month', now()->format('Y-m')) }}" class="h-11 rounded-lg border border-line px-3 font-normal">
                </label>
                <label class="grid gap-2 text-sm font-semibold">
                    <span>Minggu</span>
                    <select name="week_no" class="h-11 rounded-lg border border-line bg-white px-3 font-normal">
                        <option value="">Semua minggu</option>
                        @foreach ([1, 2] as $week)
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
                <div data-salary-advance-fields class="grid gap-4 sm:col-span-2 sm:grid-cols-2">
                    <label class="grid gap-2 text-sm font-semibold">
                        <span>Potongan DP Gaji</span>
                        <select name="salary_advance_type" class="h-11 rounded-lg border border-line bg-white px-3 font-normal">
                            <option value="">Tidak ada</option>
                            <option value="fixed" @selected(old('salary_advance_type') === 'fixed')>Fixed (Rupiah)</option>
                            <option value="percentage" @selected(old('salary_advance_type') === 'percentage')>Persentase gaji borongan</option>
                        </select>
                    </label>
                    <label data-salary-advance-value class="hidden grid gap-2 text-sm font-semibold">
                        <span data-salary-advance-value-label>Nilai DP Gaji</span>
                        <div class="relative">
                            <x-rupiah-input name="salary_advance_value" :value="old('salary_advance_value')" :decimals="3" placeholder="Masukkan nilai DP" data-salary-advance-value-input />
                            <span data-salary-advance-suffix class="pointer-events-none absolute right-3.5 top-1/2 hidden -translate-y-1/2 text-sm font-medium text-slate-500">%</span>
                        </div>
                    </label>
                </div>
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
                    <span data-deduction-employee-count class="text-xs text-slate-500">{{ $employees->count() }} karyawan aktif</span>
                </div>
                <div data-deduction-employees data-deduction-employee-url="{{ route('deductions.employee-options') }}" class="mt-3 grid max-h-72 gap-1 overflow-y-auto sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($employees as $employee)
                        <label class="flex min-w-0 items-center gap-2 rounded-md border border-transparent px-2 py-1 text-xs hover:border-line hover:bg-slate-50">
                            <input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}" @checked(in_array($employee->id, old('employee_ids', []))) class="size-4 shrink-0 rounded border-line text-primary-600" data-deduction-employee>
                            <span class="truncate font-medium text-slate-800">{{ $employee->employee_no }} — {{ $employee->full_name }}</span>
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
