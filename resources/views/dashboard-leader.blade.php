@php
    $formatMoney = fn (int|float|string $amount): string => 'Rp '.number_format((float) $amount, 0, ',', '.');
    $payrollDeduction = $payrollSummary['deductions'];
    $shiftChart = $shiftSummaries->map(fn (object $summary): array => ['label' => $summary->shift?->name ?? 'Tanpa shift', 'value' => (float) $summary->total_output])->values()->all();
    $kpis = [
        ['label' => 'Realisasi area hari ini', 'value' => number_format($metrics['realizationsToday'], 0, ',', '.'), 'helper' => 'Group yang menjadi lingkup Anda', 'icon' => 'clipboard-document-list'],
        ['label' => 'Output area hari ini', 'value' => number_format($metrics['outputToday'], 3, ',', '.'), 'helper' => 'Total output group Anda', 'icon' => 'cube'],
        ['label' => 'Batch aktif area', 'value' => number_format($metrics['activeBatches'], 0, ',', '.'), 'helper' => 'Produk pada group Anda', 'icon' => 'document-chart-bar'],
        ['label' => 'Karyawan terlibat', 'value' => number_format($metrics['assignedEmployeesToday'], 0, ',', '.'), 'helper' => 'Assignment group hari ini', 'icon' => 'users'],
        ['label' => 'Belum di-assign', 'value' => number_format($metrics['unassignedToday'], 0, ',', '.'), 'helper' => 'Realisasi area hari ini', 'icon' => 'bell'],
    ];
@endphp

<x-layouts.app
    title="Dashboard Leader"
    active="dashboard"
    :clients="$availableClients"
    :current-client="$currentClient"
    :user="$user"
>
    <section class="mb-6 flex flex-col items-start justify-between gap-4 xl:flex-row xl:items-end xl:gap-6">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs text-slate-500">
                <x-icon name="home" size="size-4" />
                <x-icon name="chevron-right" size="size-4" />
                <span>Dashboard Leader</span>
            </div>
            <h1 class="text-[clamp(1.65rem,2.2vw,2rem)] font-semibold leading-tight tracking-[-0.035em] text-slate-950">Ringkasan operasional</h1>
            <p class="mt-1.5 text-sm text-slate-500">Pantau realisasi dan assignment pada area kerja Anda.</p>
            @if ($areaGroupName)
                <p class="mt-1 text-xs font-medium text-primary-700">Lingkup data: {{ $areaGroupName }}</p>
            @else
                <p class="mt-1 text-xs font-medium text-amber-700">Group belum ditetapkan pada profil Anda. Data area belum tersedia.</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('realizations.create') }}" class="inline-flex h-10 items-center gap-2 rounded-lg bg-primary-600 px-3.5 text-xs font-semibold text-white shadow-sm transition hover:bg-primary-700"><x-icon name="plus" size="size-4" /> Tambah realisasi</a>
            <a href="{{ route('realizations.index') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-line bg-white px-3.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"><x-icon name="clipboard-document-list" size="size-4" /> Lihat realisasi</a>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="Ringkasan KPI leader">
        @foreach ($kpis as $kpi)
            <article class="flex min-h-[118px] items-start gap-3 rounded-card border border-line bg-white p-4 shadow-card">
                <span class="grid size-10 shrink-0 place-items-center rounded-[10px] bg-primary-50 text-primary-600"><x-icon :name="$kpi['icon']" size="size-5" /></span>
                <div class="min-w-0">
                    <p class="text-xs leading-5 text-slate-600">{{ $kpi['label'] }}</p>
                    <p class="mt-1 text-2xl font-semibold leading-tight tracking-[-0.03em] text-slate-950">{{ $kpi['value'] }}</p>
                    <p class="mt-1.5 text-[11px] text-slate-500">{{ $kpi['helper'] }}</p>
                </div>
            </article>
        @endforeach
    </section>

    <section class="mt-3 grid min-w-0 gap-3 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]" aria-label="Grafik operasional">
        <x-card class="min-w-0" :padding="false">
            <div class="flex min-h-[60px] items-center justify-between gap-3 border-b border-slate-100 px-4 sm:px-5"><div><h2 class="text-sm font-semibold text-slate-900">Tren output area</h2><p class="mt-0.5 text-[11px] text-slate-500">7 hari terakhir</p></div><span class="rounded-full bg-primary-50 px-2.5 py-1 text-[11px] font-semibold text-primary-700">{{ number_format(collect($outputTrend)->sum('value'), 3, ',', '.') }} total</span></div>
            <div class="p-4 sm:p-5"><div data-dashboard-output-chart data-values='@json($outputTrend)' class="min-h-[230px]" role="img" aria-label="Grafik tren output area tujuh hari terakhir"></div><div class="mt-3 grid grid-cols-7 gap-1 text-center text-[10px] text-slate-400">@foreach ($outputTrend as $point)<span><strong class="block font-semibold text-slate-600">{{ number_format($point['value'], 0, ',', '.') }}</strong>{{ $point['label'] }}</span>@endforeach</div></div>
        </x-card>
        <x-card class="min-w-0" :padding="false">
            <div class="flex min-h-[60px] items-center justify-between gap-3 border-b border-slate-100 px-4 sm:px-5"><div><h2 class="text-sm font-semibold text-slate-900">Output area per shift</h2><p class="mt-0.5 text-[11px] text-slate-500">Distribusi output group Anda</p></div><x-icon name="chart-bar" size="size-5" class="text-primary-600" /></div>
            @if ($shiftSummaries->isEmpty())
                <p class="px-4 py-12 text-center text-sm text-slate-500 sm:px-5">Belum ada data shift pada area ini.</p>
            @else
                <div class="p-4 sm:p-5"><div data-dashboard-shift-chart data-values='@json($shiftChart)' class="min-h-[230px]" role="img" aria-label="Grafik output area berdasarkan shift"></div></div>
            @endif
        </x-card>
    </section>

    <section class="mt-3 grid min-w-0 gap-3 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
        <x-card class="min-w-0" :padding="false">
            <div class="flex min-h-[55px] items-center justify-between gap-3 border-b border-slate-100 px-4 sm:px-5">
                <h2 class="text-sm font-semibold text-slate-900">Realisasi terbaru area</h2>
                <a href="{{ route('realizations.index') }}" class="text-xs font-semibold text-primary-600">Lihat semua →</a>
            </div>
            @if ($recentRealizations->isEmpty())
                <p class="px-4 py-10 text-center text-sm text-slate-500 sm:px-5">Belum ada realisasi pada area ini.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[680px] border-collapse text-left text-xs">
                        <thead class="bg-slate-50/80 text-[11px] font-semibold uppercase tracking-[0.06em] text-slate-500">
                            <tr><th class="px-3 py-2.5">Tanggal</th><th class="px-3 py-2.5">Produk</th><th class="px-3 py-2.5">Shift</th><th class="px-3 py-2.5">Batch</th><th class="px-3 py-2.5">Output</th><th class="px-3 py-2.5">Status</th><th class="px-3 py-2.5"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-line text-slate-600">
                            @foreach ($recentRealizations as $realization)
                                <tr>
                                    <td class="whitespace-nowrap px-3 py-2.5">{{ $realization->work_date?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="max-w-[180px] truncate px-3 py-2.5" title="{{ $realization->product_name_snapshot }}">{{ $realization->product_name_snapshot ?: '—' }}</td>
                                    <td class="px-3 py-2.5">{{ $realization->shift?->name ?? '—' }}</td>
                                    <td class="px-3 py-2.5">{{ $realization->batch?->batch_no ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-3 py-2.5">{{ number_format($realization->total_output, 3, ',', '.') }}</td>
                                    <td class="whitespace-nowrap px-3 py-2.5"><x-badge :variant="$realization->employee_assignments_count > 0 ? 'success' : 'warning'">{{ $realization->employee_assignments_count > 0 ? 'Sudah di-assign' : 'Belum di-assign' }}</x-badge></td>
                                    <td class="whitespace-nowrap px-3 py-2.5"><a href="{{ route('realizations.show', $realization) }}" class="font-semibold text-primary-600">Detail</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <x-card class="min-w-0" :padding="false">
            <div class="flex min-h-[55px] items-center border-b border-slate-100 px-4 sm:px-5"><h2 class="text-sm font-semibold text-slate-900">Output per shift</h2></div>
            @if ($shiftSummaries->isEmpty())
                <p class="px-4 py-10 text-center text-sm text-slate-500 sm:px-5">Belum ada ringkasan shift.</p>
            @else
                <div class="grid gap-3 p-4 sm:p-5">
                    @foreach ($shiftSummaries as $summary)
                        <div class="rounded-lg border border-line px-3 py-3">
                            <div class="flex items-center justify-between gap-3"><span class="text-xs font-semibold text-slate-700">{{ $summary->shift?->name ?? 'Tanpa shift' }}</span><span class="text-[11px] text-slate-500">{{ $summary->realizations_count }} realisasi</span></div>
                            <p class="mt-1 text-lg font-semibold text-slate-950">{{ number_format($summary->total_output, 3, ',', '.') }}</p>
                            <p class="text-[11px] text-slate-500">Total output group Anda</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </section>

    <section class="mt-3 grid min-w-0 gap-3 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.7fr)]">
        <x-card class="min-w-0" :padding="false">
            <div class="flex min-h-[55px] items-center justify-between gap-3 border-b border-slate-100 px-4 sm:px-5"><h2 class="text-sm font-semibold text-slate-900">Gaji saya bulan ini</h2><span class="text-xs text-slate-500">{{ now()->locale('id')->translatedFormat('F Y') }}</span></div>
            @if (! $payroll)
                <p class="px-4 py-10 text-center text-sm text-slate-500 sm:px-5">Belum ada data gaji bulan ini.</p>
            @else
                <div class="grid gap-3 p-4 sm:grid-cols-3 sm:p-5">
                    <div class="rounded-lg border border-line px-3 py-3"><p class="text-xs text-slate-500">Gaji kotor</p><p class="mt-1 text-base font-semibold text-slate-950">{{ $formatMoney($payrollSummary['gross']) }}</p></div>
                    <div class="rounded-lg border border-red-100 bg-red-50 px-3 py-3"><p class="text-xs text-red-700">Total pengurang</p><p class="mt-1 text-base font-semibold text-red-800">{{ $formatMoney($payrollDeduction) }}</p></div>
                    <div class="rounded-lg border {{ $payrollNet < 0 ? 'border-rose-200 bg-rose-50' : 'border-emerald-100 bg-emerald-50' }} px-3 py-3"><p class="text-xs {{ $payrollNet < 0 ? 'text-rose-700' : 'text-emerald-700' }}">{{ $payrollNet < 0 ? 'Defisit gaji' : 'Gaji bersih' }}</p><p class="mt-1 text-base font-semibold {{ $payrollNet < 0 ? 'text-rose-800' : 'text-emerald-800' }}">{{ $formatMoney($payrollNet) }}</p></div>
                </div>
                <div class="flex items-center justify-between border-t border-line px-4 py-3 sm:px-5"><span class="text-xs text-slate-500">Hari kerja tercatat: <strong class="text-slate-700">{{ $payroll->attendance_days }}</strong></span><a href="{{ route('reports.payroll') }}" class="text-xs font-semibold text-primary-600">Lihat detail gaji →</a></div>
            @endif
        </x-card>

        <x-card class="min-w-0" :padding="false">
            <div class="flex min-h-[55px] items-center border-b border-slate-100 px-4 sm:px-5"><h2 class="text-sm font-semibold text-slate-900">Perlu perhatian</h2></div>
            <div class="grid gap-3 p-4 sm:p-5">
                @if ($metrics['unassignedToday'] > 0)
                    <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3"><x-icon name="bell" size="size-5" class="shrink-0 text-amber-600" /><div><p class="text-xs font-semibold text-amber-900">{{ $metrics['unassignedToday'] }} realisasi belum di-assign</p><p class="mt-0.5 text-[11px] text-amber-800">Assign karyawan agar pekerjaan bisa dipantau.</p></div></div>
                @endif
                @if ($metrics['complaintsToday'] > 0)
                    <div class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-3"><x-icon name="bell" size="size-5" class="shrink-0 text-red-600" /><div><p class="text-xs font-semibold text-red-900">{{ $metrics['complaintsToday'] }} komplain hari ini</p><p class="mt-0.5 text-[11px] text-red-800">Periksa detail realisasi terkait.</p></div></div>
                @endif
                @if ($metrics['unassignedToday'] === 0 && $metrics['complaintsToday'] === 0)
                    <div class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3"><x-icon name="check-circle" size="size-5" class="shrink-0 text-emerald-600" /><div><p class="text-xs font-semibold text-emerald-900">Tidak ada perhatian khusus</p><p class="mt-0.5 text-[11px] text-emerald-800">Data operasional hari ini terlihat aman.</p></div></div>
                @endif
            </div>
        </x-card>
    </section>
</x-layouts.app>
