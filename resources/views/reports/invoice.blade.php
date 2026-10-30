<x-layouts.app title="Report Invoice" active="invoice-report" :current-client="$currentClient" :user="$user">
<div class="mb-6"><p class="text-xs text-slate-500">Laporan / Report Invoice</p><h1 class="mt-2 text-2xl font-semibold text-slate-950">Report Invoice</h1><p class="mt-1 text-sm text-slate-500">Rekap hasil realisasi per periode, SKU, dan cost center beserta total PO.</p></div>
<x-card>
    <div data-invoice-report-filters class="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 xl:items-end">
        <label class="grid min-w-0 gap-2 text-sm font-semibold">
            <span>Dari periode</span>
            <input data-invoice-report-from type="month" value="{{ $periodFrom }}" class="h-11 w-full min-w-0 rounded-lg border border-line px-3 font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100">
        </label>
        <label class="grid min-w-0 gap-2 text-sm font-semibold">
            <span>Sampai periode</span>
            <input data-invoice-report-to type="month" value="{{ $periodTo }}" class="h-11 w-full min-w-0 rounded-lg border border-line px-3 font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100">
        </label>
        <div class="flex min-w-0 gap-2 sm:col-span-2 xl:col-span-2 xl:justify-end">
            <button type="button" data-invoice-report-submit class="inline-flex h-11 min-w-0 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white transition hover:bg-primary-700 focus-visible:outline-2 focus-visible:outline-primary-600 sm:flex-none sm:min-w-36">
                <x-icon name="funnel" size="size-4"/> Terapkan
            </button>
            <a data-invoice-report-export href="{{ route('reports.invoice.export.excel') }}" class="inline-flex h-11 min-w-0 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 sm:flex-none sm:min-w-36">
                <x-icon name="arrow-down-tray" size="size-4"/> Export
            </a>
        </div>
    </div>
</x-card>
<div class="mt-5 rounded-xl border border-line bg-white px-5 py-4"><p class="text-xs text-slate-500">Total PO sesuai filter</p><p data-invoice-report-total class="mt-1 text-xl font-semibold text-slate-900">Rp 0</p></div>
<x-card class="mt-5"><div class="max-w-full min-w-0 overflow-x-auto"><table data-invoice-report-table data-url="{{ route('reports.invoice') }}" class="w-full min-w-[860px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="w-14 px-4 py-3">No</th><th class="px-4 py-3">Periode</th><th class="px-4 py-3">SKU</th><th class="px-4 py-3">Cost Center</th><th class="px-4 py-3 text-right">Qty</th><th class="px-4 py-3">Satuan</th><th class="px-4 py-3 text-right">Harga PO</th><th class="px-4 py-3 text-right">Total PO</th></tr></thead><tbody></tbody></table></div></x-card>
</x-layouts.app>
