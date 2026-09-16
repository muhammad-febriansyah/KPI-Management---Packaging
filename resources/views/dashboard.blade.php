@php
    $kpis = [
        ['label' => 'Total realisasi hari ini', 'value' => number_format($metrics['realizations'], 0, ',', '.'), 'helper' => 'Data aktual hari ini', 'icon' => 'clipboard-document-list'],
        ['label' => 'Output hari ini', 'value' => number_format($metrics['output'], 3, ',', '.'), 'helper' => 'Total output aktual', 'icon' => 'cube'],
        ['label' => 'Karyawan aktif', 'value' => number_format($metrics['employees'], 0, ',', '.'), 'helper' => 'Pada client aktif', 'icon' => 'users'],
        ['label' => 'Produk aktif', 'value' => number_format($metrics['products'], 0, ',', '.'), 'helper' => 'Pada client aktif', 'icon' => 'cube'],
        ['label' => 'Total komplain', 'value' => number_format($metrics['complaints'], 0, ',', '.'), 'helper' => 'Total tercatat', 'icon' => 'shield-check'],
    ];
    $outputRows = [
        ['date' => 'Hari ini 14:32', 'product' => 'Sachet Kopi 25g', 'shift' => 'Pagi', 'output' => '2.400'],
        ['date' => 'Hari ini 13:10', 'product' => 'Teh Celup 2g', 'shift' => 'Siang', 'output' => '1.800'],
        ['date' => 'Hari ini 11:05', 'product' => 'Gula Sachet 5g', 'shift' => 'Pagi', 'output' => '3.200'],
        ['date' => 'Hari ini 09:20', 'product' => 'Minuman Serbuk 20g', 'shift' => 'Pagi', 'output' => '1.600'],
    ];
    $activities = [
        ['time' => '14:35', 'activity' => 'Menambahkan realisasi pekerjaan', 'detail' => 'Produk: Teh Celup 2g (1.800 pcs)', 'user' => 'Siti Rahma'],
        ['time' => '13:10', 'activity' => 'Login ke sistem', 'detail' => null, 'user' => 'Budi Santoso'],
        ['time' => '11:22', 'activity' => 'Mengubah data produk', 'detail' => 'Sachet Kopi 25g', 'user' => 'Admin'],
        ['time' => '09:15', 'activity' => 'Menambahkan karyawan baru', 'detail' => null, 'user' => 'Admin'],
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

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="Ringkasan KPI">
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

    <section class="mt-3 grid min-w-0 gap-3 xl:grid-cols-[minmax(0,1.25fr)_minmax(360px,0.75fr)]">
        <x-card class="min-w-0" :padding="false">
            <div class="flex min-h-[55px] items-center justify-between gap-3 border-b border-slate-100 px-4 sm:px-5">
                <h2 class="text-sm font-semibold text-slate-900">Tren output 7 hari</h2>
                <select class="h-9 rounded-lg border border-line bg-white px-2.5 text-xs text-slate-600 outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100" aria-label="Periode tren">
                    <option>7 Hari Terakhir</option>
                    <option>30 Hari Terakhir</option>
                </select>
            </div>
            <div class="overflow-x-auto p-4 sm:p-5">
                <svg class="h-[235px] min-w-[620px] w-full" viewBox="0 0 700 250" role="img" aria-label="Grafik batang tren output tujuh hari">
                    <g stroke="#E9EDF3" stroke-width="1"><line x1="52" y1="20" x2="680" y2="20"/><line x1="52" y1="70" x2="680" y2="70"/><line x1="52" y1="120" x2="680" y2="120"/><line x1="52" y1="170" x2="680" y2="170"/><line x1="52" y1="220" x2="680" y2="220"/></g>
                    <g fill="#7C8AA0" font-size="10"><text x="6" y="23">20.000</text><text x="6" y="73">15.000</text><text x="6" y="123">10.000</text><text x="16" y="173">5.000</text><text x="37" y="223">0</text></g>
                    <g fill="#81A7FF"><rect x="83" y="137" width="46" height="83" rx="5"/><rect x="170" y="119" width="46" height="101" rx="5"/><rect x="257" y="124" width="46" height="96" rx="5"/><rect x="344" y="106" width="46" height="114" rx="5"/><rect x="431" y="95" width="46" height="125" rx="5"/><rect x="518" y="88" width="46" height="132" rx="5"/></g><rect x="605" y="95" width="46" height="125" rx="5" fill="#2547F9"/>
                    <g fill="#4B5F7C" font-size="10" font-weight="600" text-anchor="middle"><text x="106" y="129">8.240</text><text x="193" y="111">10.120</text><text x="280" y="116">9.560</text><text x="367" y="98">11.340</text><text x="454" y="87">12.480</text><text x="541" y="80">13.200</text><text x="628" y="87">12.480</text></g>
                    <g fill="#7C8AA0" font-size="10" text-anchor="middle"><text x="106" y="241">12 Apr</text><text x="193" y="241">13 Apr</text><text x="280" y="241">14 Apr</text><text x="367" y="241">15 Apr</text><text x="454" y="241">16 Apr</text><text x="541" y="241">17 Apr</text><text x="628" y="241">18 Apr</text></g>
                </svg>
            </div>
        </x-card>

        <x-card class="min-w-0" :padding="false">
            <div class="flex min-h-[55px] items-center justify-between gap-3 border-b border-slate-100 px-4 sm:px-5">
                <h2 class="text-sm font-semibold text-slate-900">Output per shift</h2>
                <select class="h-9 rounded-lg border border-line bg-white px-2.5 text-xs text-slate-600 outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100" aria-label="Periode output shift"><option>Hari Ini</option><option>Minggu Ini</option></select>
            </div>
            <div class="grid min-h-[250px] items-center gap-6 p-5 sm:grid-cols-[190px_1fr]">
                <div class="relative mx-auto grid size-[178px] place-items-center rounded-full" style="background: conic-gradient(#2547F9 0 41%, #6D95FF 41% 76%, #B7CCFF 76% 100%)">
                    <div class="absolute inset-7 grid place-content-center rounded-full bg-white text-center"><strong class="text-[21px] text-slate-950">12.480</strong><span class="mt-0.5 text-[10px] text-slate-400">Total output</span></div>
                </div>
                <div class="grid gap-4 text-xs text-slate-600">
                    @foreach ([['Shift pagi', '5.120', '41%', '#2547F9'], ['Shift siang', '4.320', '35%', '#6D95FF'], ['Shift malam', '3.040', '24%', '#B7CCFF']] as $shift)
                        <div class="grid grid-cols-[auto_1fr_auto_auto] items-center gap-2.5"><span class="size-2.5 rounded-full" style="background: {{ $shift[3] }}"></span><span>{{ $shift[0] }}</span><strong class="text-slate-900">{{ $shift[1] }}</strong><span class="w-8 text-right text-slate-500">{{ $shift[2] }}</span></div>
                    @endforeach
                </div>
            </div>
        </x-card>
    </section>

    <section class="mt-3 grid min-w-0 gap-3 xl:grid-cols-[1.08fr_0.92fr]">
        <x-card class="min-w-0" :padding="false">
            <div class="flex min-h-[55px] items-center justify-between gap-3 border-b border-slate-100 px-4 sm:px-5"><h2 class="text-sm font-semibold text-slate-900">Realisasi terbaru</h2><span class="text-xs font-semibold text-primary-600">Lihat semua →</span></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[620px] border-collapse text-left text-xs"><thead class="bg-slate-50/80 text-[11px] font-semibold uppercase tracking-[0.06em] text-slate-500"><tr><th class="px-3 py-2.5">Tanggal</th><th class="px-3 py-2.5">Produk</th><th class="px-3 py-2.5">Shift</th><th class="px-3 py-2.5">Output</th><th class="px-3 py-2.5">Status</th></tr></thead><tbody class="divide-y divide-line text-slate-600">@foreach ($outputRows as $row)<tr><td class="whitespace-nowrap px-3 py-2.5">{{ $row['date'] }}</td><td class="px-3 py-2.5">{{ $row['product'] }}</td><td class="px-3 py-2.5">{{ $row['shift'] }}</td><td class="px-3 py-2.5">{{ $row['output'] }}</td><td class="px-3 py-2.5"><x-badge variant="success">Selesai</x-badge></td></tr>@endforeach</tbody></table></div>
        </x-card>
        <x-card class="min-w-0" :padding="false">
            <div class="flex min-h-[55px] items-center justify-between gap-3 border-b border-slate-100 px-4 sm:px-5"><h2 class="text-sm font-semibold text-slate-900">Aktivitas terbaru</h2><span class="text-xs font-semibold text-primary-600">Lihat semua →</span></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[620px] border-collapse text-left text-xs"><thead class="bg-slate-50/80 text-[11px] font-semibold uppercase tracking-[0.06em] text-slate-500"><tr><th class="px-3 py-2.5">Waktu</th><th class="px-3 py-2.5">Aktivitas</th><th class="px-3 py-2.5">User</th></tr></thead><tbody class="divide-y divide-line text-slate-600">@foreach ($activities as $activity)<tr><td class="whitespace-nowrap px-3 py-2.5">{{ $activity['time'] }}</td><td class="px-3 py-2.5">{{ $activity['activity'] }}@if ($activity['detail'])<span class="mt-0.5 block text-[11px] text-slate-400">{{ $activity['detail'] }}</span>@endif</td><td class="whitespace-nowrap px-3 py-2.5">{{ $activity['user'] }}</td></tr>@endforeach</tbody></table></div>
        </x-card>
    </section>

    <aside data-filter-panel class="fixed inset-y-0 right-0 z-[80] w-full max-w-[420px] translate-x-full border-l border-line bg-white shadow-[-18px_0_40px_rgb(15_23_42/0.1)] transition-transform duration-200" aria-label="Filter dashboard">
        <div class="flex h-[68px] items-center justify-between border-b border-line px-5"><strong class="text-sm text-slate-900">Filter dashboard</strong><button type="button" data-filter-close class="grid size-9 place-items-center rounded-lg text-slate-500 hover:bg-slate-50" aria-label="Tutup filter"><x-icon name="x-mark" /></button></div>
        <div class="grid gap-4 p-5"><label class="grid gap-2 text-xs font-semibold text-slate-600">Rentang tanggal<input type="text" data-datepicker data-datepicker-mode="range" autocomplete="off" placeholder="Pilih rentang tanggal" class="h-11 rounded-lg border border-slate-300 px-3 text-sm font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100"></label><label class="grid gap-2 text-xs font-semibold text-slate-600">Shift<select class="h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100"><option>Semua shift</option><option>Pagi</option><option>Siang</option><option>Malam</option></select></label><label class="grid gap-2 text-xs font-semibold text-slate-600">Produk<select class="h-11 rounded-lg border border-slate-300 bg-white px-3 text-sm font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100"><option>Semua produk</option><option>Sachet Kopi 25g</option><option>Teh Celup 2g</option></select></label><button type="button" class="h-11 rounded-lg bg-primary-600 text-sm font-semibold text-white">Terapkan filter</button><button type="button" data-filter-close class="h-11 rounded-lg border border-line bg-white text-sm font-semibold text-slate-600">Reset filter</button></div>
    </aside>
</x-layouts.app>
