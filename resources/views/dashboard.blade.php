@php
    $kpis = [
        ['label' => 'Output hari ini', 'value' => number_format($metrics['output'], 3, ',', '.'), 'helper' => 'Total output aktual', 'icon' => 'cube'],
        ['label' => 'Output bulan ini', 'value' => number_format($metrics['outputThisMonth'], 3, ',', '.'), 'helper' => 'Total output periode berjalan', 'icon' => 'cube'],
        ['label' => 'Pencapaian target bulan ini', 'value' => $metrics['targetAchievement'] === null ? '—' : number_format($metrics['targetAchievement'], 0, ',', '.').'%', 'helper' => $metrics['targetAchievement'] === null ? 'Target belum tersedia' : 'Estimasi target '.number_format($metrics['targetThisMonth'], 3, ',', '.').' unit', 'icon' => 'chart-bar'],
    ];
@endphp

<x-layouts.app
    title="Dashboard"
    active="dashboard"
    :clients="$availableClients"
    :current-client="$currentClient"
    :user="$user"
>
    <section class="mb-6 flex flex-col items-start justify-between gap-4 xl:flex-row xl:gap-6">
        <div>
            <div class="mb-2 flex items-center gap-2 text-xs text-slate-500">
                <x-icon name="home" size="size-4" />
                <x-icon name="chevron-right" size="size-4" />
                <span>Dashboard</span>
            </div>
            <h1 class="text-[clamp(1.65rem,2.2vw,2rem)] font-semibold leading-tight tracking-[-0.035em] text-slate-950">Ringkasan performa</h1>
            <p class="mt-1.5 text-sm text-slate-500">Pantau hasil pekerjaan dan performa co-packing dari client yang sedang aktif.</p>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3" aria-label="Ringkasan KPI">
        @foreach ($kpis as $kpi)
            <article class="flex min-h-[118px] items-start gap-3.5 rounded-card border border-line bg-white p-[18px] shadow-card">
                <span class="grid size-[42px] shrink-0 place-items-center rounded-[10px] bg-primary-50 text-primary-600"><x-icon :name="$kpi['icon']" /></span>
                <div class="min-w-0">
                    <p class="text-xs leading-5 text-slate-600">{{ $kpi['label'] }}</p>
                    <p class="mt-1 text-2xl font-semibold leading-tight tracking-[-0.03em] text-slate-950">{{ $kpi['value'] }}</p>
                    <p @class([
                        'mt-1.5 flex items-center gap-1 whitespace-nowrap text-[11px]',
                        'text-success' => isset($kpi['trend']),
                        'text-slate-500' => ! isset($kpi['trend']),
                    ])>
                        @if (($kpi['trend'] ?? null) === 'up') <x-icon name="arrow-trending-up" size="size-3.5" /> @endif
                        @if (($kpi['trend'] ?? null) === 'down') <x-icon name="arrow-trending-down" size="size-3.5" /> @endif
                        {{ $kpi['helper'] }}
                    </p>
                </div>
            </article>
        @endforeach
    </section>

    <section class="mt-3 grid min-w-0 gap-3">
        <x-card class="min-w-0" :padding="false">
            <div class="flex min-h-[55px] items-center justify-between gap-3 border-b border-slate-100 px-4 sm:px-5">
                <h2 class="text-sm font-semibold text-slate-900">Tren output bulanan</h2>
            </div>
            <div class="overflow-x-auto p-4 sm:p-5">
                <div data-dashboard-output-chart data-values='@json($outputTrend)' class="h-[235px] min-w-[620px] w-full" role="img" aria-label="Grafik tren output bulanan"></div>
            </div>
        </x-card>

        <x-card class="min-w-0" :padding="false">
            <div class="flex min-h-[55px] items-center justify-between gap-3 border-b border-slate-100 px-4 sm:px-5">
                <h2 class="text-sm font-semibold text-slate-900">Output per shift bulan ini</h2>
            </div>
            <div class="p-4 sm:p-5">
                <div data-dashboard-shift-chart data-values='@json($shiftTrend)' class="min-h-[250px]" role="img" aria-label="Grafik output per shift bulan ini"></div>
            </div>
        </x-card>
    </section>

    <section class="mt-3 grid min-w-0 gap-3">
        <x-card class="min-w-0" :padding="false">
            <div class="flex min-h-[55px] items-center justify-between gap-3 border-b border-slate-100 px-4 sm:px-5">
                <h2 class="text-sm font-semibold text-slate-900">Realisasi terbaru</h2>
                <a href="{{ route('reports.work') }}" class="text-xs font-semibold text-primary-600 hover:text-primary-700">Lihat laporan →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[620px] border-collapse text-left text-xs">
                    <thead class="bg-slate-50/80 text-[11px] font-semibold uppercase tracking-[0.06em] text-slate-500">
                        <tr><th class="px-3 py-2.5">Tanggal</th><th class="px-3 py-2.5">Produk</th><th class="px-3 py-2.5">Shift</th><th class="px-3 py-2.5">Output</th><th class="px-3 py-2.5">Keterangan</th></tr>
                    </thead>
                    <tbody class="divide-y divide-line text-slate-600">
                        @forelse ($recentRealizations as $realization)
                            <tr>
                                <td class="whitespace-nowrap px-3 py-2.5">{{ $realization->work_date?->translatedFormat('d M Y') ?? '—' }}</td>
                                <td class="px-3 py-2.5">{{ $realization->product?->name ?? $realization->product_name_snapshot ?? '—' }}</td>
                                <td class="px-3 py-2.5">{{ $realization->shift?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-3 py-2.5">{{ number_format((float) $realization->total_output, 3, ',', '.') }}</td>
                                <td class="px-3 py-2.5">{{ $realization->is_complaint ? 'Ada komplain' : 'Normal' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-8 text-center text-slate-500">Belum ada realisasi pada client ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </section>
</x-layouts.app>
