<x-layouts.app title="Detail Realisasi" active="realizations" :current-client="$currentClient" :user="$user">
<div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><div class="mb-3 flex items-center gap-2 text-xs font-medium text-slate-500"><span>Transaksi</span><x-icon name="chevron-right" size="size-3.5"/><span>Realisasi</span><x-icon name="chevron-right" size="size-3.5"/><span class="text-primary-600">Detail</span></div><h1 class="text-3xl font-semibold tracking-tight text-slate-950">Detail Realisasi Pekerjaan</h1><p class="mt-2 text-sm text-slate-500">Ringkasan hasil pekerjaan dan informasi pendukung.</p></div><a href="{{ route('realizations.index') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-line bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"><x-icon name="chevron-right" class="rotate-180" size="size-4"/> Kembali</a></div>
@php
    $rate = $realization->employeeAssignments->first()?->rate_per_unit_snapshot ?? $realization->product?->employee_rate ?? 0;
    $totalPrice = (float) ($realization->total_output ?? 0) * (float) $rate;
@endphp
<x-card>
    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Data realisasi pekerjaan</h2>
            <p class="mt-1 text-sm text-slate-500">Informasi sesuai Form Realisasi Pekerjaan.</p>
        </div>
    </div>
    <dl class="grid gap-x-8 gap-y-5 sm:grid-cols-2">
        <div><dt class="text-xs text-slate-500">Tanggal borongan</dt><dd class="mt-1.5 font-semibold text-slate-900">{{ $realization->work_date?->format('d/m/Y') ?? '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">Shift</dt><dd class="mt-1.5 font-semibold text-slate-900">{{ $realization->shift?->name ?? '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">Nomor batch</dt><dd class="mt-1.5 font-semibold text-slate-900">{{ $realization->batch?->batch_no ?? '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">SKU</dt><dd class="mt-1.5 font-semibold text-slate-900">{{ $realization->sku_snapshot ?? '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">Nama produk</dt><dd class="mt-1.5 font-semibold text-slate-900">{{ $realization->product_name_snapshot ?? '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">Total (Karton/Kg)</dt><dd class="mt-1.5 font-semibold text-slate-900">{{ $realization->total_output !== null ? rtrim(rtrim(number_format((float) $realization->total_output, 3, ',', '.'), '0'), ',').' '.($realization->unit_name_snapshot ?? '') : '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">Total harga</dt><dd class="mt-1.5 font-semibold text-slate-900">Rp {{ number_format($totalPrice, 0, ',', '.') }}</dd></div>
        <div><dt class="text-xs text-slate-500">Waktu pengerjaan (1)</dt><dd class="mt-1.5 font-semibold text-slate-900">{{ $realization->start_time ? substr($realization->start_time, 0, 5) : '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">Waktu pengerjaan (2)</dt><dd class="mt-1.5 font-semibold text-slate-900">{{ $realization->end_time ? substr($realization->end_time, 0, 5) : '—' }}</dd></div>
    </dl>
    <div class="mt-6 border-t border-line pt-5">
        <h3 class="text-sm font-semibold text-slate-900">Report</h3>
        <div class="mt-3 min-h-24 text-sm leading-6 text-slate-600 [&_a]:text-primary-600 [&_a]:underline [&_blockquote]:border-l-2 [&_blockquote]:border-line [&_blockquote]:pl-3 [&_blockquote]:text-slate-500 [&_ol]:mb-3 [&_ol]:list-decimal [&_ol]:pl-5 [&_p:last-child]:mb-0 [&_p]:mb-3 [&_ul]:mb-3 [&_ul]:list-disc [&_ul]:pl-5">{!! $realization->report ?: '<p class="text-slate-500">Tidak ada report untuk realisasi ini.</p>' !!}</div>
    </div>
</x-card>
<x-card class="mt-5">
    <h2 class="text-base font-semibold text-slate-900">Foto hasil pekerjaan</h2>
    @if($realization->result_image_path)
        <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($realization->result_image_path) }}" target="_blank" rel="noopener" class="mt-4 block">
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($realization->result_image_path) }}" alt="Foto hasil pekerjaan" class="max-h-96 w-full rounded-xl border border-line object-contain">
        </a>
    @else
        <div data-realization-image-placeholder class="mt-4 grid min-h-64 place-items-center rounded-xl border border-dashed border-line bg-slate-50 px-6 py-10 text-center">
            <div>
                <span class="mx-auto grid size-12 place-items-center rounded-full bg-white text-slate-400 shadow-sm ring-1 ring-slate-200"><x-icon name="photo" size="size-6" /></span>
                <p class="mt-3 text-sm font-semibold text-slate-600">No image</p>
                <p class="mt-1 text-xs text-slate-400">Belum ada foto hasil pekerjaan.</p>
            </div>
        </div>
    @endif
</x-card>
<x-card class="mt-5"><div class="mb-4 flex items-center justify-between"><h2 class="text-base font-semibold text-slate-900">Karyawan yang ditugaskan</h2><span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700">{{ $realization->employeeAssignments->count() }} orang</span></div>@if($realization->employeeAssignments->isEmpty())<p class="text-sm text-slate-500">Belum ada karyawan yang ditugaskan.</p>@else<div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">@foreach($realization->employeeAssignments as $assignment)<div class="flex items-center gap-3 rounded-lg border border-line bg-slate-50 px-3 py-2.5 transition hover:border-primary-200 hover:bg-primary-50/40"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-primary-100 text-xs font-semibold text-primary-700 ring-2 ring-white">{{ mb_strtoupper(mb_substr($assignment->employee?->full_name ?? '?', 0, 1)) }}</span><div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-800">{{ $assignment->employee?->full_name ?? '—' }}</p><p class="text-xs text-slate-500">{{ $assignment->employee?->employee_no ?? '—' }}</p></div></div>@endforeach</div>@endif</x-card>
</x-layouts.app>
