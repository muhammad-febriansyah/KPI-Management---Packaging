@php
    $formatMoney = fn (int|float|string $amount): string => 'Rp '.number_format((float) $amount, 0, ',', '.');
    $netSalary = $payrollSummary['net'];
    $netIsNegative = $netSalary < 0;
    $kpis = [
        ['label' => 'Hari kerja', 'value' => $payroll ? number_format($payroll->attendance_days, 0, ',', '.') : '—', 'helper' => 'Bulan berjalan', 'icon' => 'chart-bar'],
        ['label' => 'Output hari ini', 'value' => number_format($metrics['outputToday'], 3, ',', '.'), 'helper' => 'Realisasi pribadi', 'icon' => 'cube'],
        ['label' => 'Realisasi bulan ini', 'value' => number_format($metrics['realizationsThisMonth'], 0, ',', '.'), 'helper' => 'Pekerjaan tercatat', 'icon' => 'clipboard-document-list'],
        ['label' => 'Output bulan ini', 'value' => number_format($metrics['outputThisMonth'], 3, ',', '.'), 'helper' => 'Akumulasi pribadi', 'icon' => 'document-chart-bar'],
    ];
@endphp

<x-layouts.app title="Dashboard Karyawan" active="dashboard" :clients="$availableClients" :current-client="$currentClient" :user="$user">
    <section class="mb-6 flex flex-col items-start justify-between gap-4 lg:flex-row lg:items-end">
        <div>
            <div class="mb-3 flex items-center gap-2 text-xs font-medium text-slate-500"><x-icon name="home" size="size-4" /><x-icon name="chevron-right" size="size-4" /><span>Dashboard Karyawan</span></div>
            <p class="text-sm font-medium text-primary-600">Halo, {{ $employee?->full_name ?? $user->name }}</p>
            <h1 class="mt-1 text-[clamp(1.8rem,3vw,2.35rem)] font-semibold leading-tight tracking-[-0.04em] text-slate-950">Ringkasan gaji saya</h1>
            <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500">Pantau hasil kerja, estimasi penghasilan, dan status gaji pada client aktif.</p>
        </div>
        <div class="flex flex-wrap gap-2"><span class="inline-flex h-10 items-center gap-2 rounded-lg border border-line bg-white px-3.5 text-xs font-semibold text-slate-600"><x-icon name="shield-check" size="size-4" class="text-emerald-600" /> Data pribadi</span><a href="{{ route('reports.payroll') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-primary-600 px-3.5 text-xs font-semibold text-white shadow-sm transition hover:bg-primary-700"><x-icon name="document-chart-bar" size="size-4" /> Lihat gaji saya</a></div>
    </section>

    <section class="relative overflow-hidden rounded-card border border-primary-100 bg-gradient-to-br from-primary-50 via-white to-slate-50 p-5 text-slate-950 shadow-card sm:p-6" aria-label="Ringkasan gaji utama">
        <div class="pointer-events-none absolute -right-20 -top-24 size-64 rounded-full bg-primary-200/40 blur-3xl"></div>
        <div class="relative grid gap-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
            <div>
                <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500"><span>Estimasi gaji bersih</span><span class="rounded-full border border-primary-100 bg-white px-2 py-1 font-medium text-slate-600">{{ now()->locale('id')->translatedFormat('F Y') }}</span></div>
                @if (! $payroll)
                    <p class="mt-3 text-2xl font-semibold tracking-[-0.03em] text-slate-900">Belum tersedia</p><p class="mt-1 text-xs text-slate-500">Data muncul setelah realisasi dan potongan diproses.</p>
                @else
                    <p class="mt-2 text-[clamp(2rem,5vw,3.2rem)] font-semibold leading-none tracking-[-0.05em] {{ $netIsNegative ? 'text-rose-600' : 'text-primary-700' }}">{{ $formatMoney($netSalary) }}</p>
                    <p class="mt-3 max-w-lg text-xs leading-5 {{ $netIsNegative ? 'text-rose-600' : 'text-slate-500' }}">{{ $netIsNegative ? 'Total pengurang saat ini lebih besar dari gaji kotor. Periksa detail potongan.' : 'Estimasi diterima setelah potongan dan koreksi.' }}</p>
                @endif
            </div>
            <div class="grid grid-cols-2 gap-2 lg:min-w-[300px]"><div class="rounded-xl border border-primary-100 bg-white/80 p-3"><p class="text-[11px] text-slate-500">Gaji kotor</p><p class="mt-1 text-sm font-semibold text-slate-900">{{ $payroll ? $formatMoney($payrollSummary['gross']) : '—' }}</p></div><div class="rounded-xl border border-rose-100 bg-rose-50/70 p-3"><p class="text-[11px] text-rose-600">Total pengurang</p><p class="mt-1 text-sm font-semibold text-rose-700">{{ $payroll ? $formatMoney($payrollSummary['deductions']) : '—' }}</p></div><div class="col-span-2 rounded-xl border border-primary-100 bg-white/80 p-3"><p class="text-[11px] text-slate-500">Client aktif</p><p class="mt-1 truncate text-sm font-semibold text-slate-900">{{ $currentClient?->name ?? '—' }}</p></div></div>
        </div>
    </section>

    <section class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Aktivitas pribadi">
        @foreach ($kpis as $kpi)
            <article class="flex items-center gap-3 rounded-card border border-line bg-white p-4 shadow-card"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary-50 text-primary-600"><x-icon :name="$kpi['icon']" size="size-5" /></span><div class="min-w-0"><p class="text-xs text-slate-500">{{ $kpi['label'] }}</p><p class="mt-1 truncate text-xl font-semibold tracking-[-0.03em] text-slate-950">{{ $kpi['value'] }}</p><p class="mt-0.5 text-[11px] text-slate-400">{{ $kpi['helper'] }}</p></div></article>
        @endforeach
    </section>

    <section class="mt-3 grid min-w-0 gap-3 xl:grid-cols-[minmax(0,1.25fr)_minmax(320px,0.75fr)]">
        <x-card class="min-w-0" :padding="false">
            <div class="flex min-h-[60px] items-center justify-between gap-3 border-b border-slate-100 px-4 sm:px-5"><div><h2 class="text-sm font-semibold text-slate-900">Tren output pribadi</h2><p class="mt-0.5 text-[11px] text-slate-500">7 hari terakhir</p></div><span class="rounded-full bg-primary-50 px-2.5 py-1 text-[11px] font-semibold text-primary-700">{{ number_format(collect($outputTrend)->sum('value'), 3, ',', '.') }} total</span></div>
            <div class="p-4 sm:p-5"><div data-dashboard-output-chart data-values='@json($outputTrend)' class="min-h-[230px]" role="img" aria-label="Grafik tren output pribadi tujuh hari terakhir"></div><div class="mt-3 grid grid-cols-7 gap-1 text-center text-[10px] text-slate-400" aria-label="Ringkasan nilai output harian">@foreach ($outputTrend as $point)<span><strong class="block font-semibold text-slate-600">{{ number_format($point['value'], 0, ',', '.') }}</strong>{{ $point['label'] }}</span>@endforeach</div></div>
        </x-card>
        <x-card class="min-w-0" :padding="false">
            <div class="flex min-h-[60px] items-center justify-between gap-3 border-b border-slate-100 px-4 sm:px-5"><div><h2 class="text-sm font-semibold text-slate-900">Rincian periode</h2><p class="mt-0.5 text-[11px] text-slate-500">Komponen perhitungan gaji</p></div><x-icon name="document-chart-bar" size="size-5" class="text-primary-600" /></div>
            @if (! $payroll)
                <div class="px-4 py-12 text-center sm:px-5"><p class="text-sm font-semibold text-slate-700">Belum ada data gaji bulan ini</p><p class="mt-1 text-xs text-slate-500">Data akan muncul setelah periode diproses.</p></div>
            @else
                <dl class="divide-y divide-line px-4 sm:px-5"><div class="flex items-center justify-between gap-3 py-3 text-xs"><dt class="text-slate-500">BPJS Kesehatan</dt><dd class="font-semibold text-slate-800">{{ $formatMoney($payrollSummary['bpjsHealth']) }}</dd></div><div class="flex items-center justify-between gap-3 py-3 text-xs"><dt class="text-slate-500">BPJS Ketenagakerjaan</dt><dd class="font-semibold text-slate-800">{{ $formatMoney($payrollSummary['bpjsEmployment']) }}</dd></div><div class="flex items-center justify-between gap-3 py-3 text-xs"><dt class="text-slate-500">Potongan lain</dt><dd class="font-semibold text-slate-800">{{ $formatMoney(max(0, $payrollSummary['deductions'] - $payrollSummary['bpjsHealth'] - $payrollSummary['bpjsEmployment'])) }}</dd></div><div class="flex items-center justify-between gap-3 py-3 text-xs"><dt class="text-slate-500">Koreksi penambahan</dt><dd class="font-semibold text-emerald-700">+ {{ $formatMoney($payrollSummary['correctionPlus']) }}</dd></div></dl>
                <div class="flex items-center justify-between border-t border-line px-4 py-3 sm:px-5"><span class="text-xs text-slate-500">{{ number_format($payroll->attendance_days, 0, ',', '.') }} hari kerja</span><a href="{{ route('reports.payroll') }}" class="text-xs font-semibold text-primary-600">Buka laporan →</a></div>
            @endif
        </x-card>
    </section>

    <section class="mt-3"><x-card :padding="false"><div class="flex min-h-[60px] items-center justify-between border-b border-slate-100 px-4 sm:px-5"><h2 class="text-sm font-semibold text-slate-900">Profil saya</h2><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">{{ $employee?->employee_no ?? $user->username }}</span></div><dl class="grid gap-3 p-4 sm:grid-cols-3 sm:p-5"><div><dt class="text-[11px] text-slate-500">Nama lengkap</dt><dd class="mt-1 text-sm font-semibold text-slate-900">{{ $employee?->full_name ?? $user->name }}</dd></div><div><dt class="text-[11px] text-slate-500">Group</dt><dd class="mt-1 text-sm font-semibold text-slate-900">{{ $employee?->group?->name ?? '—' }}</dd></div><div><dt class="text-[11px] text-slate-500">Client</dt><dd class="mt-1 truncate text-sm font-semibold text-slate-900">{{ $currentClient?->name ?? '—' }}</dd></div></dl></x-card></section>
</x-layouts.app>
