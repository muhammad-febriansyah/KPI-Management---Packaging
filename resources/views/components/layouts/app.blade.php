@props([
    'title' => null,
    'active' => 'dashboard',
    'clientName' => null,
    'userName' => null,
    'clients' => [],
    'currentClient' => null,
    'user' => null,
])

@php
    $resolvedUserName = $user?->name ?? $userName ?? 'Admin Operasional';
    $resolvedUserRole = $user?->is_super_admin ? 'Super Admin' : 'Administrator';
    $roleCode = $user?->roleCodeFor($currentClient);
    $resolvedUserRole = match ($roleCode) {
        'employee' => 'Karyawan',
        'client' => 'Client',
        'super-admin' => 'Super Admin',
        default => 'Administrator',
    };
    $allowedNavigation = $user?->allowedMenuKeys($currentClient);
    // Client has no notification triggers yet (see WorkRealizationController), so the bell
    // stays hidden for them — skip the queries too, not just the UI.
    $recentNotifications = $user && $roleCode !== 'client' ? $user->notifications()->latest()->limit(8)->get() : collect();
    $unreadNotificationsCount = $user && $roleCode !== 'client' ? $user->unreadNotifications()->count() : 0;
    $userInitials = collect(explode(' ', trim($resolvedUserName)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $navigation = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home', 'route' => 'dashboard'],
        ['key' => 'master-data', 'label' => 'Master Data', 'icon' => 'building-office-2', 'children' => [
            ['key' => 'units', 'label' => 'Satuan', 'route' => 'units.index'],
            ['key' => 'groups', 'label' => 'Group', 'route' => 'groups.index'],
            ['key' => 'cost-centers', 'label' => 'Cost Center', 'icon' => 'building-office-2', 'route' => 'cost-centers.index'],
            ['key' => 'products', 'label' => 'Produk', 'icon' => 'cube', 'route' => 'products.index'],
            ['key' => 'employees', 'label' => 'Karyawan', 'icon' => 'users', 'route' => 'employees.index'],
            ['key' => 'clients', 'label' => 'Client', 'icon' => 'building-office-2', 'route' => 'clients.index'],
            ['key' => 'shifts', 'label' => 'Master Shift', 'icon' => 'clock', 'route' => 'shifts.index', 'hidden' => true],
        ]],
        ['key' => 'pemborongan', 'label' => 'Pemborongan', 'icon' => 'clipboard-document-list', 'children' => [
            ['key' => 'target', 'label' => 'Target', 'hidden' => true],
            ['key' => 'realizations', 'label' => 'Realisasi', 'route' => 'realizations.index'],
            ['key' => 'deductions', 'label' => 'Potongan Gaji', 'route' => 'deductions.index'],
        ]],
        ['key' => 'reports-group', 'label' => 'Laporan', 'icon' => 'document-chart-bar', 'children' => [
            ['key' => 'work-reports', 'label' => 'Hasil Pekerjaan', 'route' => 'reports.work'],
            ['key' => 'reports', 'label' => 'Gaji', 'route' => 'reports.payroll'],
        ]],
        ['key' => 'settings-group', 'label' => 'Setting', 'icon' => 'shield-check', 'children' => [
            ['key' => 'settings', 'label' => 'User', 'route' => 'settings.access'],
            ['key' => 'audit', 'label' => 'Audit Log', 'route' => 'audit.index', 'hidden' => true],
        ]],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#2547F9">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ? $title.' — ' : '' }}{{ config('app.name') === 'Laravel' ? 'KPI Co-Packing' : config('app.name') }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/png">
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="min-h-dvh overflow-x-hidden bg-canvas font-sans text-sm antialiased" @if (session('status') || session('success')) data-flash-status="{{ session('status') ?? session('success') }}" @endif @if (session('error')) data-flash-error="{{ session('error') }}" @endif>
        <div data-sidebar-overlay class="fixed inset-0 z-40 hidden bg-slate-950/35 lg:hidden"></div>

        <aside data-sidebar class="fixed inset-y-0 left-0 z-50 flex w-[250px] -translate-x-full flex-col border-r border-line bg-white shadow-[16px_0_40px_rgb(15_23_42/0.08)] transition-transform duration-200 ease-out lg:translate-x-0 lg:shadow-none" aria-label="Navigasi utama">
            <div class="flex min-h-[72px] items-center justify-between overflow-hidden border-b border-line px-[18px]">
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="flex items-center gap-3 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary-600">
                    <img src="{{ asset('images/logo-white.png') }}" alt="SIMGROUP" class="w-[150px] shrink-0">
                </a>
                <button type="button" data-sidebar-toggle class="grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-ink focus-visible:outline-2 focus-visible:outline-primary-600 lg:hidden" aria-label="Tutup navigasi" aria-expanded="false">
                    <x-icon name="x-mark" />
                </button>
            </div>

            <nav class="flex flex-1 flex-col gap-1 overflow-y-auto px-3 py-[18px]">
                @foreach ($navigation as $item)
                    @php
                        $visibleChildren = isset($item['children'])
                            ? collect($item['children'])->filter(fn (array $child): bool => ! ($child['hidden'] ?? false) && ($allowedNavigation === null || in_array($child['key'], $allowedNavigation, true)))->values()
                            : collect();
                    @endphp
                    @continue(($item['hidden'] ?? false) || ($allowedNavigation !== null && (isset($item['children']) ? $visibleChildren->isEmpty() : ! in_array($item['key'], $allowedNavigation, true))))
                    @if (isset($item['children']))
                        @php $isGroupActive = $visibleChildren->pluck('key')->contains($active); @endphp
                        <div data-nav-group>
                            <button type="button" data-nav-toggle @class([
                                'group flex min-h-[42px] w-full cursor-pointer items-center gap-3 rounded-lg px-3 text-[13px] font-medium transition-colors focus-visible:outline-2 focus-visible:outline-primary-600',
                                'text-primary-600 font-semibold' => $isGroupActive,
                                'text-slate-600 hover:bg-slate-50 hover:text-ink' => ! $isGroupActive,
                            ]) aria-expanded="{{ $isGroupActive ? 'true' : 'false' }}">
                                <span class="grid size-6 shrink-0 place-items-center"><x-icon :name="$item['icon']" /></span>
                                <span class="flex-1 truncate text-left">{{ $item['label'] }}</span>
                                <span data-nav-chevron @class([
                                    'shrink-0 text-slate-400 transition-transform duration-200 ease-out',
                                    'rotate-180' => $isGroupActive,
                                ])>
                                    <x-icon name="chevron-down" size="size-4" />
                                </span>
                            </button>
                            <div data-nav-panel @class([
                                'grid transition-[grid-template-rows] duration-300 ease-out',
                                'grid-rows-[1fr]' => $isGroupActive,
                                'grid-rows-[0fr]' => ! $isGroupActive,
                            ])>
                                <div class="overflow-hidden">
                                    <div class="flex flex-col gap-1 py-1 pl-[34px]">
                                        @foreach ($visibleChildren as $child)
                                            @if (isset($child['route']))
                                                <a href="{{ route($child['route']) }}" @class([
                                                    'flex min-h-[38px] items-center gap-2 rounded-lg px-3 text-[13px] font-medium transition-colors focus-visible:outline-2 focus-visible:outline-primary-600',
                                                    'bg-primary-50 text-primary-600 font-semibold' => $active === $child['key'],
                                                    'text-slate-600 hover:bg-slate-50 hover:text-ink' => $active !== $child['key'],
                                                ])>
                                                    <x-icon name="chevron-right" size="size-3.5" class="shrink-0 text-slate-400" />
                                                    <span class="truncate">{{ $child['label'] }}</span>
                                                </a>
                                            @else
                                                <span class="flex min-h-[38px] items-center gap-2 rounded-lg px-3 text-[13px] font-medium text-slate-400" aria-disabled="true" title="Modul segera tersedia">
                                                    <x-icon name="chevron-right" size="size-3.5" class="shrink-0 text-slate-300" />
                                                    <span class="truncate">{{ $child['label'] }}</span>
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif (isset($item['route']))
                        <a href="{{ route($item['route']) }}" @class([
                            'group flex min-h-[42px] items-center gap-3 rounded-lg px-3 text-[13px] font-medium transition-colors focus-visible:outline-2 focus-visible:outline-primary-600',
                            'bg-primary-50 text-primary-600 font-semibold' => $active === $item['key'],
                            'text-slate-600 hover:bg-slate-50 hover:text-ink' => $active !== $item['key'],
                        ])>
                            <span class="grid size-6 shrink-0 place-items-center"><x-icon :name="$item['icon']" /></span>
                            <span class="truncate">{{ $item['label'] }}</span>
                        </a>
                    @else
                        <span @class([
                            'group flex min-h-[42px] items-center gap-3 rounded-lg px-3 text-[13px] font-medium',
                            'bg-primary-50 text-primary-600 font-semibold' => $active === $item['key'],
                            'text-slate-600' => $active !== $item['key'],
                        ]) aria-disabled="true" title="Modul segera tersedia">
                            <span class="grid size-6 shrink-0 place-items-center"><x-icon :name="$item['icon']" /></span>
                            <span class="truncate">{{ $item['label'] }}</span>
                        </span>
                    @endif
                @endforeach
            </nav>

            <div class="border-t border-[#F2F4F8] px-6 py-5 text-[11px] leading-5 text-slate-400">
                KPI Management Co-Packing<br>Multi-client workspace
            </div>
        </aside>

        <div data-sidebar-content class="min-h-dvh lg:pl-[250px]">
            <header class="sticky top-0 z-30 flex h-[72px] items-center gap-3 border-b border-line bg-white/95 px-4 backdrop-blur-sm sm:px-6">
                <button type="button" data-sidebar-toggle class="grid size-[42px] shrink-0 cursor-pointer place-items-center rounded-lg border border-line bg-white text-slate-600 hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-primary-600" aria-label="Tampilkan/sembunyikan navigasi" aria-expanded="true">
                    <x-icon name="bars-3" />
                </button>

                @if (! in_array($roleCode, ['client', 'employee'], true))
                    <div data-global-search class="relative min-w-0 max-w-[520px] flex-1">
                        <label class="relative block">
                            <span class="sr-only">Pencarian global</span>
                            <x-icon name="magnifying-glass" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500" />
                            <input type="search" data-global-search-input autocomplete="off" placeholder="Cari data karyawan, produk, atau laporan..." class="h-[42px] w-full rounded-lg border border-line bg-white pl-11 pr-4 text-[13px] text-ink outline-none placeholder:text-slate-400 focus:border-primary-600 focus:ring-3 focus:ring-primary-100">
                        </label>
                        <div data-global-search-results class="absolute left-0 top-[calc(100%+8px)] z-50 hidden max-h-[70vh] w-full overflow-y-auto rounded-xl border border-line bg-white p-2 shadow-[0_14px_38px_rgb(15_23_42/0.12)]"></div>
                    </div>
                @endif

                <div class="flex-1"></div>

                @unless ($roleCode === 'client')
                <div class="relative shrink-0">
                    <button type="button" data-dropdown-button="notifications-dropdown" class="relative grid size-[42px] shrink-0 cursor-pointer place-items-center rounded-lg text-slate-600 hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-primary-600" aria-label="Notifikasi" aria-haspopup="true">
                        <x-icon name="bell" />
                        @if ($unreadNotificationsCount > 0)
                            <span class="absolute right-2 top-2 size-2 rounded-full border-2 border-white bg-red-500"></span>
                        @endif
                    </button>
                    <div id="notifications-dropdown" class="absolute right-0 top-[calc(100%+8px)] z-50 hidden w-80 max-w-[90vw] rounded-xl border border-line bg-white shadow-[0_14px_38px_rgb(15_23_42/0.12)]" data-dropdown-menu>
                        <div class="flex items-center justify-between border-b border-line px-4 py-3">
                            <span class="text-xs font-semibold text-ink">Notifikasi</span>
                            @if ($unreadNotificationsCount > 0)
                                <button type="button" data-notifications-read-all class="cursor-pointer text-[11px] font-semibold text-primary-600 hover:underline">Tandai semua dibaca</button>
                            @endif
                        </div>
                        <div class="max-h-96 overflow-y-auto">
                            @forelse ($recentNotifications as $notification)
                                <a href="{{ $notification->data['url'] ?? '#' }}" data-notification-open data-id="{{ $notification->id }}" class="flex gap-2.5 border-b border-line px-4 py-3 last:border-0 hover:bg-slate-50 {{ $notification->read_at ? '' : 'bg-primary-50/50' }}">
                                    <span class="mt-1.5 size-1.5 shrink-0 rounded-full {{ $notification->read_at ? '' : 'bg-primary-600' }}"></span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-xs font-semibold text-ink">{{ $notification->data['title'] ?? 'Notifikasi' }}</span>
                                        <span class="mt-0.5 block text-[11px] leading-snug text-slate-500">{{ $notification->data['body'] ?? '' }}</span>
                                        <span class="mt-1 block text-[10px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                                    </span>
                                </a>
                            @empty
                                <p class="px-4 py-8 text-center text-xs text-slate-500">Belum ada notifikasi.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                @endunless
                <div class="relative shrink-0">
                    <button type="button" data-dropdown-button="profile-dropdown" class="flex cursor-pointer items-center gap-2 rounded-lg p-1.5 text-left hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-primary-600" aria-haspopup="true">
                        @if ($user?->avatar_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_path) }}" alt="Avatar {{ $resolvedUserName }}" class="size-9 rounded-full object-cover">
                        @else
                            <span class="grid size-9 place-items-center rounded-full bg-slate-200 text-xs font-bold text-slate-600">{{ $userInitials ?: 'AO' }}</span>
                        @endif
                        <span class="hidden leading-[1.15] lg:block"><strong class="block text-xs font-semibold text-ink">{{ $resolvedUserName }}</strong><span class="block text-[10px] text-slate-500">{{ $resolvedUserRole }}</span></span>
                        <x-icon name="chevron-down" size="size-4" class="hidden text-slate-500 lg:block" />
                    </button>
                    <div id="profile-dropdown" class="absolute right-0 top-[calc(100%+8px)] z-50 hidden min-w-48 rounded-xl border border-line bg-white p-2 shadow-[0_14px_38px_rgb(15_23_42/0.12)]" data-dropdown-menu>
                        @if (auth()->check())
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                <x-icon name="user" size="size-4" />
                                Profil saya
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full cursor-pointer items-center gap-2 rounded-lg px-3 py-2.5 text-left text-xs font-medium text-red-600 hover:bg-red-50">
                                    <x-icon name="arrow-right-start-on-rectangle" size="size-4" />
                                    Keluar dari sistem
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1660px] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                {{ $slot }}
            </main>
        </div>

        @stack('scripts')
    </body>
</html>
