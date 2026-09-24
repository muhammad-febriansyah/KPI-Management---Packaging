@php
    $kpis = [
        ['label' => 'Realisasi hari ini', 'value' => number_format($metrics['realizationsToday'], 0, ',', '.'), 'helper' => 'Yang saya input hari ini', 'icon' => 'clipboard-document-list'],
        ['label' => 'Output hari ini', 'value' => number_format($metrics['outputToday'], 3, ',', '.'), 'helper' => 'Total output saya hari ini', 'icon' => 'cube'],
        ['label' => 'Realisasi bulan ini', 'value' => number_format($metrics['realizationsThisMonth'], 0, ',', '.'), 'helper' => 'Total sepanjang bulan berjalan', 'icon' => 'chart-bar'],
        ['label' => 'Total realisasi saya', 'value' => number_format($metrics['realizationsTotal'], 0, ',', '.'), 'helper' => 'Sepanjang waktu', 'icon' => 'document-chart-bar'],
    ];
@endphp

<x-layouts.app
    title="Dashboard"
    active="dashboard"
    :clients="$availableClients"
    :current-client="$currentClient"
    :user="$user"
>
    <section class="mb-6">
        <div class="mb-2 flex items-center gap-2 text-xs text-slate-500">
            <x-icon name="home" size="size-4" />
            <x-icon name="chevron-right" size="size-4" />
            <span>Dashboard</span>
        </div>
        <h1 class="text-[clamp(1.65rem,2.2vw,2rem)] font-semibold leading-tight tracking-[-0.035em] text-slate-950">Ringkasan performa saya</h1>
        <p class="mt-1.5 text-sm text-slate-500">Realisasi pekerjaan yang sudah saya catat pada client ini.</p>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan KPI">
        @foreach ($kpis as $kpi)
            <article class="flex min-h-[118px] items-start gap-3.5 rounded-card border border-line bg-white p-[18px] shadow-card">
                <span class="grid size-[42px] shrink-0 place-items-center rounded-[10px] bg-primary-50 text-primary-600"><x-icon :name="$kpi['icon']" /></span>
                <div class="min-w-0">
                    <p class="text-xs leading-5 text-slate-600">{{ $kpi['label'] }}</p>
                    <p class="mt-1 text-2xl font-semibold leading-tight tracking-[-0.03em] text-slate-950">{{ $kpi['value'] }}</p>
                    <p class="mt-1.5 text-[11px] text-slate-500">{{ $kpi['helper'] }}</p>
                </div>
            </article>
        @endforeach
    </section>

    <section class="mt-3">
        <x-card class="min-w-0" :padding="false">
            <div class="flex min-h-[55px] items-center border-b border-slate-100 px-4 sm:px-5"><h2 class="text-sm font-semibold text-slate-900">Tren output 7 hari</h2></div>
            @php $maxOutput = max(1, collect($outputTrend)->max('value')); @endphp
            <div class="flex items-end justify-between gap-2 p-5" style="height: 190px">
                @foreach ($outputTrend as $day)
                    <div class="flex h-full flex-1 flex-col items-center justify-end gap-1.5">
                        <span class="text-[10px] font-semibold text-slate-600">{{ $day['value'] > 0 ? number_format($day['value'], 0, ',', '.') : '' }}</span>
                        <div class="w-full rounded-t-md {{ $day['value'] > 0 ? 'bg-primary-500' : 'bg-slate-100' }}" style="height: {{ max(4, (int) round(($day['value'] / $maxOutput) * 130)) }}px"></div>
                        <span class="text-[10px] text-slate-500">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </x-card>
    </section>

    <section class="mt-3">
        <x-card class="min-w-0" :padding="false">
            <div class="flex min-h-[55px] items-center justify-between gap-3 border-b border-slate-100 px-4 sm:px-5">
                <h2 class="text-sm font-semibold text-slate-900">Potongan gaji bulan ini</h2>
                <span class="text-xs text-slate-500">{{ now()->translatedFormat('F Y') }}</span>
            </div>
            @if (! $deduction)
                <p class="px-4 py-10 text-center text-sm text-slate-500 sm:px-5">Belum ada data potongan gaji untuk bulan ini.</p>
            @else
                @php
                    $deductionItems = [
                        ['Seragam', $deduction->uniform_amount],
                        ['Perlengkapan kerja', $deduction->equipment_amount],
                        ['Uang makan', $deduction->meal_amount],
                        ['Koreksi pengurangan', $deduction->correction_minus],
                    ];
                    $totalFixedDeduction = $deduction->uniform_amount + $deduction->equipment_amount + $deduction->meal_amount + $deduction->correction_minus;
                    // "1.00%" is harder to scan than "1%" — keep decimals only when they matter.
                    $formatPercent = fn (float|string $value): string => rtrim(rtrim(number_format((float) $value, 2), '0'), '.').'%';
                @endphp
                <div class="grid gap-3 p-4 sm:grid-cols-2 sm:px-5">
                    @foreach ($deductionItems as [$label, $amount])
                        <div class="rounded-lg border border-line px-3 py-2.5"><p class="text-xs text-slate-500">{{ $label }}</p><p class="mt-0.5 text-sm font-semibold text-slate-900">Rp {{ number_format($amount, 0, ',', '.') }}</p></div>
                    @endforeach
                    <div class="rounded-lg border border-line px-3 py-2.5"><p class="text-xs text-slate-500">BPJS Kesehatan</p><p class="mt-0.5 text-sm font-semibold text-slate-900">{{ $formatPercent($deduction->bpjs_health_percent) }}</p></div>
                    <div class="rounded-lg border border-line px-3 py-2.5"><p class="text-xs text-slate-500">BPJS Ketenagakerjaan</p><p class="mt-0.5 text-sm font-semibold text-slate-900">{{ $formatPercent($deduction->bpjs_employment_percent) }}</p></div>
                    @if ($deduction->correction_plus > 0)
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2.5 sm:col-span-2"><p class="text-xs text-emerald-700">Koreksi penambahan</p><p class="mt-0.5 text-sm font-semibold text-emerald-800">+ Rp {{ number_format($deduction->correction_plus, 0, ',', '.') }}</p></div>
                    @endif
                </div>
                <div class="flex items-center justify-between border-t border-line px-4 py-3 sm:px-5"><span class="text-xs font-semibold text-slate-600">Total potongan tetap</span><span class="text-sm font-bold text-red-600">Rp {{ number_format($totalFixedDeduction, 0, ',', '.') }}</span></div>
                @if ($deduction->notes)
                    <p class="border-t border-line px-4 py-3 text-xs text-slate-500 sm:px-5">Catatan: {{ $deduction->notes }}</p>
                @endif
            @endif
        </x-card>
    </section>

    <section class="mt-3">
        <x-card class="min-w-0" :padding="false">
            <div class="flex min-h-[55px] items-center justify-between gap-3 border-b border-slate-100 px-4 sm:px-5">
                <h2 class="text-sm font-semibold text-slate-900">Realisasi terbaru saya</h2>
                <a href="{{ route('realizations.index') }}" class="text-xs font-semibold text-primary-600">Lihat semua →</a>
            </div>
            @if ($recentRealizations->isEmpty())
                <p class="px-4 py-10 text-center text-sm text-slate-500 sm:px-5">Belum ada realisasi yang ditugaskan kepada Anda.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[620px] border-collapse text-left text-xs">
                        <thead class="bg-slate-50/80 text-[11px] font-semibold uppercase tracking-[0.06em] text-slate-500">
                            <tr><th class="px-3 py-2.5">Tanggal</th><th class="px-3 py-2.5">Produk</th><th class="px-3 py-2.5">Shift</th><th class="px-3 py-2.5">Output</th><th class="px-3 py-2.5"></th></tr>
                        </thead>
                        <tbody class="divide-y divide-line text-slate-600">
                            @foreach ($recentRealizations as $realization)
                                <tr>
                                    <td class="whitespace-nowrap px-3 py-2.5">{{ $realization->work_date?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="px-3 py-2.5">{{ $realization->product_name_snapshot }}</td>
                                    <td class="px-3 py-2.5">{{ $realization->shift?->name ?? '—' }}</td>
                                    <td class="px-3 py-2.5">{{ number_format($realization->total_output, 3, ',', '.') }}</td>
                                    <td class="whitespace-nowrap px-3 py-2.5"><a href="{{ route('realizations.show', $realization) }}" class="font-semibold text-primary-600">Detail</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </section>

</x-layouts.app>
