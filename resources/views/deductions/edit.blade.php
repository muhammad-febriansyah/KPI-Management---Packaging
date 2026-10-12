<x-layouts.app title="Edit Potongan Gaji" active="deductions" :current-client="$currentClient" :user="$user">
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <div class="mb-3 flex items-center gap-2 text-xs font-medium text-slate-500">
                <span>Penggajian</span><x-icon name="chevron-right" size="size-3.5" />
                <a href="{{ route('deductions.index') }}" class="hover:text-primary-600">Potongan Gaji</a><x-icon name="chevron-right" size="size-3.5" />
                <span class="text-primary-600">Edit</span>
            </div>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Edit Potongan Gaji</h1>
            <p class="mt-2 text-sm text-slate-500">Perbarui komponen potongan untuk karyawan ini.</p>
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

    <form data-deduction-edit-wizard data-initial-step="{{ $errors->any() ? 2 : 1 }}" action="{{ route('deductions.update', $deduction) }}" method="POST" class="grid gap-5">
        @csrf
        @method('PUT')
        <x-card>
            <div class="mb-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-primary-600">Langkah <span data-edit-wizard-current-label>1</span> dari 3</p>
                <h2 class="mt-1 text-lg font-semibold text-slate-900" data-edit-wizard-title tabindex="-1">Periode</h2>
                <p class="mt-1 text-sm text-slate-500" data-edit-wizard-description>Pilih client dan periode potongan gaji.</p>
            </div>
            <div class="inline-flex max-w-full gap-1 overflow-x-auto rounded-2xl bg-slate-100 p-1.5" role="tablist" aria-label="Bagian edit potongan gaji">
                <button id="deduction-edit-tab-1" type="button" role="tab" aria-selected="true" aria-controls="deduction-edit-panel-1" data-edit-wizard-tab="1" class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-primary-700 shadow-sm ring-1 ring-primary-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"><span data-edit-wizard-tab-icon class="grid size-8 place-items-center rounded-lg bg-primary-50 text-primary-600"><x-icon name="clipboard-document-list" size="size-4" /></span>Periode</button>
                <button id="deduction-edit-tab-2" type="button" role="tab" aria-selected="false" aria-controls="deduction-edit-panel-2" data-edit-wizard-tab="2" class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-white/70 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"><span data-edit-wizard-tab-icon class="grid size-8 place-items-center rounded-lg text-slate-500"><x-icon name="wallet" size="size-4" /></span>Komponen</button>
                <button id="deduction-edit-tab-3" type="button" role="tab" aria-selected="false" aria-controls="deduction-edit-panel-3" data-edit-wizard-tab="3" class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-white/70 hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"><span data-edit-wizard-tab-icon class="grid size-8 place-items-center rounded-lg text-slate-500"><x-icon name="users" size="size-4" /></span>Karyawan</button>
            </div>
        </x-card>

        <section id="deduction-edit-panel-1" data-edit-wizard-step="1" role="tabpanel" aria-labelledby="deduction-edit-tab-1" aria-label="Periode potongan" tabindex="0">
            <x-card>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-2 text-sm font-semibold sm:col-span-2"><span>Client</span><input type="text" readonly value="{{ $currentClient->code }} — {{ $currentClient->name }}" class="h-11 rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-700"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Bulan</span><input type="text" readonly value="{{ $deduction->deductionPeriod?->month?->locale('id')->translatedFormat('F Y') ?? '—' }}" class="h-11 rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-700"></label>
                    <label class="grid gap-2 text-sm font-semibold"><span>Minggu</span><input type="text" readonly value="{{ $deduction->deductionPeriod?->week_no ? 'Minggu '.$deduction->deductionPeriod->week_no : 'Semua minggu' }}" class="h-11 rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-700"></label>
                </div>
            </x-card>
        </section>

        <section id="deduction-edit-panel-2" data-edit-wizard-step="2" role="tabpanel" aria-labelledby="deduction-edit-tab-2" aria-label="Komponen potongan" class="hidden" tabindex="0">
            <x-card>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ([['uniform_amount', 'Potongan seragam (Kaos/Celana)'], ['equipment_amount', 'Potongan perlengkapan kerja'], ['meal_amount', 'Potongan uang makan'], ['correction_minus', 'Koreksi pengurangan'], ['correction_plus', 'Koreksi penambahan']] as [$field, $label])
                        <label class="grid gap-2 text-sm font-semibold">
                            <span>{{ $label }}</span>
                            <x-rupiah-input :name="$field" :value="old($field, $deduction->{$field})" placeholder="0" />
                        </label>
                    @endforeach

                    <label class="grid gap-2 text-sm font-semibold">
                        <span>BPJS Kesehatan (%)</span>
                        <input type="number" name="bpjs_health_percent" min="0" max="100" step="0.001" value="{{ old('bpjs_health_percent', $deduction->bpjs_health_percent) }}" placeholder="Contoh: 1.000" class="h-11 rounded-lg border border-line px-3 font-normal">
                    </label>
                    <label class="grid gap-2 text-sm font-semibold">
                        <span>BPJS Ketenagakerjaan (%)</span>
                        <input type="number" name="bpjs_employment_percent" min="0" max="100" step="0.001" value="{{ old('bpjs_employment_percent', $deduction->bpjs_employment_percent) }}" placeholder="Contoh: 2.000" class="h-11 rounded-lg border border-line px-3 font-normal">
                    </label>
                    <div data-salary-advance-fields class="grid gap-4">
                        <label class="grid gap-2 text-sm font-semibold">
                            <span>Potongan DP Gaji</span>
                            <select name="salary_advance_type" class="h-11 rounded-lg border border-line bg-white px-3 font-normal">
                                <option value="">Tidak ada</option>
                                <option value="fixed" @selected(old('salary_advance_type', $deduction->salary_advance_type) === 'fixed')>Fixed (Rupiah)</option>
                                <option value="percentage" @selected(old('salary_advance_type', $deduction->salary_advance_type) === 'percentage')>Persentase gaji borongan</option>
                            </select>
                        </label>
                        <label data-salary-advance-value class="hidden grid gap-2 text-sm font-semibold">
                            <span data-salary-advance-value-label>Nilai DP Gaji</span>
                            <div class="relative">
                                <x-rupiah-input name="salary_advance_value" :value="old('salary_advance_value', $deduction->salary_advance_value)" :decimals="3" placeholder="Masukkan nilai DP" data-salary-advance-value-input />
                                <span data-salary-advance-suffix class="pointer-events-none absolute right-3.5 top-1/2 hidden -translate-y-1/2 text-sm font-medium text-slate-500">%</span>
                            </div>
                        </label>
                    </div>
                    <label class="grid gap-2 text-sm font-semibold sm:col-span-2"><span>Catatan</span><textarea name="notes" rows="3" placeholder="Catatan tambahan (opsional)" class="rounded-lg border border-line px-3 py-2 font-normal">{{ old('notes', $deduction->notes) }}</textarea></label>
                </div>
            </x-card>
        </section>

        <section id="deduction-edit-panel-3" data-edit-wizard-step="3" role="tabpanel" aria-labelledby="deduction-edit-tab-3" aria-label="Karyawan dipilih" class="hidden" tabindex="0">
            <x-card>
                <fieldset>
                    <legend class="text-sm font-semibold text-slate-900">Karyawan dipilih</legend>
                    <div class="mt-3 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                        <span class="text-sm text-slate-600">Edit ini hanya mengubah potongan untuk karyawan terpilih.</span>
                        <span class="text-xs text-slate-500">1 karyawan</span>
                    </div>
                    <div class="mt-3 overflow-x-auto rounded-lg border border-line">
                        <table class="w-full min-w-[420px] text-left text-sm">
                            <thead><tr><th class="w-14 px-4 py-3">Pilih</th><th class="px-4 py-3">No. Karyawan — Nama</th></tr></thead>
                            <tbody><tr>
                                <td class="px-4 py-3"><input type="checkbox" checked disabled class="size-4 rounded border-line text-primary-600" aria-label="Karyawan potongan ini"></td>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $deduction->employee?->employee_no ?? '—' }} — {{ $deduction->employee?->full_name ?? '—' }}</td>
                            </tr></tbody>
                        </table>
                    </div>
                </fieldset>
            </x-card>
        </section>

        <div class="flex flex-col-reverse justify-between gap-3 sm:flex-row">
            <a href="{{ route('deductions.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-100"><x-icon name="x-mark" size="size-4" /> Batal</a>
            <div class="flex justify-end gap-3">
                <button type="button" data-edit-wizard-back class="hidden items-center gap-2 rounded-lg border border-line bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"><x-icon name="chevron-right" class="rotate-180" size="size-4" /> Sebelumnya</button>
                <button type="button" data-edit-wizard-next class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">Berikutnya <x-icon name="chevron-right" size="size-4" /></button>
                <button type="submit" data-edit-wizard-submit class="hidden items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700"><x-icon name="check-circle" size="size-4" /> Simpan perubahan</button>
            </div>
        </div>
    </form>
</x-layouts.app>
