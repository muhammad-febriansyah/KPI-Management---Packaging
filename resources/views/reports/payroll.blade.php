<x-layouts.app title="Laporan Gaji" active="reports" :current-client="$currentClient" :user="$user">
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-xs text-slate-500">Laporan / Penggajian</p>
            <h1 class="mt-2 text-2xl font-semibold text-slate-950">Laporan Gaji</h1>
            <p class="mt-1 text-sm text-slate-500">Ringkasan kehadiran, pendapatan, potongan, dan gaji bersih karyawan.</p>
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <span class="grid size-8 place-items-center rounded-lg bg-primary-50 text-primary-700"><x-icon name="document-chart-bar" size="size-4" /></span>
            <span>Export mengikuti periode aktif</span>
        </div>
    </div>

    <x-card class="mb-5">
        <div data-payroll-filters class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-end">
            <label class="grid gap-2 text-sm font-semibold">
                <span>Dari tanggal</span>
                <input data-datepicker data-payroll-date-from type="text" value="{{ $dateFrom }}" placeholder="Pilih tanggal" class="h-11 rounded-lg border border-line px-3 font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100">
            </label>
            <label class="grid gap-2 text-sm font-semibold">
                <span>Sampai tanggal</span>
                <input data-datepicker data-payroll-date-to type="text" value="{{ $dateTo }}" placeholder="Pilih tanggal" class="h-11 rounded-lg border border-line px-3 font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100">
            </label>
            <button type="button" data-payroll-filter-submit class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white transition hover:bg-primary-700 focus-visible:outline-2 focus-visible:outline-primary-600">
                <x-icon name="funnel" size="size-4" /> Terapkan filter
            </button>
        </div>
        <div class="mt-5 flex flex-col gap-3 border-t border-line pt-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-800">Unduh laporan</p>
                <p class="mt-1 text-xs text-slate-500">File berisi seluruh data payroll pada periode yang dipilih.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a data-payroll-export-excel href="{{ route('reports.payroll.export.excel', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 focus-visible:outline-2 focus-visible:outline-emerald-600">
                    <x-icon name="arrow-down-tray" size="size-4" /> Export Excel
                </a>
                <a data-payroll-export-pdf href="{{ route('reports.payroll.export.pdf', ['date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 focus-visible:outline-2 focus-visible:outline-rose-600">
                    <x-icon name="arrow-down-tray" size="size-4" /> Export PDF
                </a>
            </div>
        </div>
    </x-card>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table data-server-table data-search-placeholder="Cari NIK atau nama karyawan" class="w-full min-w-[1650px] text-left text-sm">
                <thead>
                    <tr>
                        <th>NIK</th>
                        <th>Nama lengkap</th>
                        <th>Jenis kelamin</th>
                        <th>Total hari masuk</th>
                        <th>Gaji bersih</th>
                        <th>Gaji kotor</th>
                        <th>BPJS Ketenagakerjaan</th>
                        <th>Seragam (Kaos/Celana)</th>
                        <th>Perlengkapan kerja</th>
                        <th>Uang makan</th>
                        <th>DP gaji</th>
                        <th>Koreksi pengurangan</th>
                        <th>Koreksi penambahan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </x-card>
</x-layouts.app>
