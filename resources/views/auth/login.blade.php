<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#2547F9">

        <title>Masuk — KPI Management Co-Packing</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/png">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-dvh bg-[#F8FAFE] font-sans text-ink antialiased">
        <main class="grid h-dvh overflow-hidden lg:grid-cols-[minmax(0,1.04fr)_minmax(480px,0.96fr)]">
            <section class="relative hidden h-full overflow-hidden border-r border-[#EEF1F6] bg-white px-[clamp(2.5rem,6vw,7.5rem)] py-[clamp(1.5rem,4vh,3rem)] lg:flex lg:flex-col" aria-label="Informasi aplikasi">
                <div class="absolute -left-64 top-28 size-96 rotate-45 rounded-[4rem] bg-primary-50" aria-hidden="true"></div>
                <div class="absolute -bottom-72 -right-52 size-[34rem] rounded-full bg-[#F8FAFF]" aria-hidden="true"></div>

                <img class="relative z-10 w-48" src="{{ asset('images/logo-white.png') }}" alt="SIMGROUP">

                <div class="relative z-10 mt-[clamp(1.5rem,5vh,4rem)] max-w-xl">
                    <h1 class="max-w-lg text-[clamp(1.75rem,3.2vh,3.45rem)] font-semibold leading-[1.08] tracking-[-0.04em] text-slate-950">
                        Pantau kinerja,<br>kelola operasional lebih baik
                    </h1>
                    <p class="mt-[clamp(0.75rem,2vh,1.25rem)] max-w-lg text-base leading-7 text-slate-500">
                        Satu ruang kerja untuk mengelola performa co-packing, master data, dan laporan setiap client secara rapi dan terukur.
                    </p>

                    <div class="mt-[clamp(1rem,3vh,2.25rem)] grid gap-[clamp(0.5rem,1.8vh,1.25rem)]">
                        <article class="flex items-start gap-3.5">
                            <span class="grid size-11 shrink-0 place-items-center rounded-xl border border-[#DFE6F2] bg-white text-primary-600 shadow-sm">
                                <x-icon name="users" />
                            </span>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-900">Kelola data multi-client</h2>
                                <p class="mt-1 text-[13px] leading-5 text-slate-500">Berpindah client tanpa mencampurkan data operasional antar perusahaan.</p>
                            </div>
                        </article>
                        <article class="flex items-start gap-3.5">
                            <span class="grid size-11 shrink-0 place-items-center rounded-xl border border-[#DFE6F2] bg-white text-primary-600 shadow-sm">
                                <x-icon name="chart-bar" />
                            </span>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-900">Pantau performa harian</h2>
                                <p class="mt-1 text-[13px] leading-5 text-slate-500">Lihat output, realisasi, shift, dan KPI penting dalam satu ringkasan.</p>
                            </div>
                        </article>
                        <article class="flex items-start gap-3.5">
                            <span class="grid size-11 shrink-0 place-items-center rounded-xl border border-[#DFE6F2] bg-white text-primary-600 shadow-sm">
                                <x-icon name="document-chart-bar" />
                            </span>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-900">Akses laporan lebih cepat</h2>
                                <p class="mt-1 text-[13px] leading-5 text-slate-500">Filter dan unduh laporan sesuai client serta hak akses pengguna.</p>
                            </div>
                        </article>
                    </div>
                </div>

                <img
                    class="relative z-10 mt-auto w-[min(31rem,76%)] translate-x-1/4 translate-y-12 -rotate-6 opacity-90"
                    src="{{ asset('images/dashboard-preview.svg') }}"
                    alt="Pratinjau dashboard KPI Management Co-Packing"
                >
                <p class="relative z-20 mt-[clamp(0.5rem,1.5vh,1.25rem)] text-xs text-slate-400">KPI Management Co-Packing · Sistem internal perusahaan</p>
            </section>

            <section class="relative grid h-full place-items-center overflow-hidden px-4 py-[clamp(0.75rem,3vh,3.5rem)] sm:px-8 lg:px-[clamp(2rem,6vw,6rem)]">
                <div class="absolute -right-52 top-[42%] size-80 rotate-45 rounded-[4.25rem] bg-primary-50/75" aria-hidden="true"></div>

                <div class="relative z-10 w-full max-w-[35rem]">
                    <div class="rounded-2xl border border-[#E5EAF2] bg-white px-5 py-[clamp(1rem,3vh,1.75rem)] shadow-[0_1px_2px_rgb(15_23_42/0.04),0_8px_22px_rgb(15_23_42/0.035)] sm:p-[clamp(1.25rem,3.5vh,2.5rem)] lg:p-[clamp(1.5rem,4vh,2.75rem)]">
                        <img class="mb-[clamp(0.75rem,3vh,2rem)] w-44 lg:hidden" src="{{ asset('images/logo-white.png') }}" alt="SIMGROUP">

                        <span class="inline-flex rounded-full bg-primary-50 px-3 py-1.5 text-xs font-semibold text-primary-600">Selamat datang</span>
                        <h2 class="mt-[clamp(0.5rem,1.5vh,1rem)] text-3xl font-semibold tracking-[-0.03em] text-slate-950">Masuk ke akun Anda</h2>
                        <p class="mt-[clamp(0.25rem,1vh,0.5rem)] max-w-md text-sm leading-6 text-slate-500">
                            Masuk untuk mengelola performa co-packing, master data, dan laporan client.
                        </p>

                        <form class="mt-[clamp(0.75rem,3vh,2rem)]" method="POST" action="{{ route('login.store') }}" data-login-form @if ($errors->has('login') && old('login') !== null) data-login-error="{{ $errors->first('login') }}" @endif>
                            @csrf

                            <div>
                                <label for="login" class="mb-2 block text-[13px] font-semibold text-slate-800">
                                    Username atau Email <span class="text-danger">*</span>
                                </label>
                                <div class="relative">
                                    <x-icon name="user" size="size-5" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                                    <input
                                        id="login"
                                        name="login"
                                        value="{{ old('login') }}"
                                        type="text"
                                        autocomplete="username"
                                        autofocus
                                        required
                                        class="h-12 w-full rounded-lg border bg-white pl-11 pr-4 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-primary-600 focus:ring-3 focus:ring-primary-100 {{ $errors->has('login') ? 'border-red-400' : 'border-slate-300' }}"
                                        placeholder="Masukkan username atau email"
                                        aria-describedby="login-error"
                                    >
                                </div>
                                @error('login')
                                    <p id="login-error" class="mt-1.5 text-xs text-danger">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-[clamp(0.5rem,2vh,1.25rem)]">
                                <label for="password" class="mb-2 block text-[13px] font-semibold text-slate-800">
                                    Password <span class="text-danger">*</span>
                                </label>
                                <div class="relative">
                                    <x-icon name="lock-closed" size="size-5" class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                                    <input
                                        id="password"
                                        name="password"
                                        type="password"
                                        autocomplete="current-password"
                                        required
                                        class="h-12 w-full rounded-lg border bg-white pl-11 pr-12 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-primary-600 focus:ring-3 focus:ring-primary-100 {{ $errors->has('password') ? 'border-red-400' : 'border-slate-300' }}"
                                        placeholder="Masukkan password"
                                        aria-describedby="password-error"
                                    >
                                    <button
                                        type="button"
                                        data-password-toggle
                                        class="absolute right-1.5 top-1/2 grid size-9 -translate-y-1/2 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-2 focus-visible:outline-primary-600"
                                        aria-label="Tampilkan password"
                                    >
                                        <span data-password-show><x-icon name="eye" /></span>
                                        <span data-password-hide class="hidden"><x-icon name="eye-slash" /></span>
                                    </button>
                                </div>
                                @error('password')
                                    <p id="password-error" class="mt-1.5 text-xs text-danger">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="my-[clamp(0.75rem,2.5vh,1.5rem)] flex items-center gap-4">
                                <label class="flex items-center gap-2 text-[13px] text-slate-700">
                                    <input name="remember" value="1" type="checkbox" class="size-4 rounded border-slate-300 accent-primary-600">
                                    <span>Ingat saya</span>
                                </label>
                            </div>

                            <button
                                type="submit"
                                class="flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 text-sm font-semibold text-white transition hover:bg-primary-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:cursor-wait disabled:opacity-70"
                            >
                                <span data-submit-label>Masuk</span>
                                <span data-submit-loading class="hidden items-center gap-2">
                                    <span class="size-4 animate-spin rounded-full border-2 border-white/40 border-t-white motion-reduce:animate-none"></span>
                                    Memproses...
                                </span>
                            </button>

                            <p class="mt-[clamp(0.75rem,2.5vh,1.75rem)] border-t border-line pt-[clamp(0.5rem,2vh,1.25rem)] text-center text-xs leading-5 text-slate-400">
                                Hanya pengguna yang memiliki akses yang dapat masuk ke sistem.
                            </p>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
