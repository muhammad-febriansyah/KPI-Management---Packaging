@php
    $isEmployee = $employee !== null;
    $isClient = $roleCode === 'client' && $currentClient !== null;
    $userInitials = collect(explode(' ', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<x-layouts.app title="Profil Saya" active="profile" :current-client="$currentClient" :user="$user">
    <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <div class="mb-3 flex items-center gap-2 text-xs font-medium text-slate-500">
                <span>Pengaturan</span>
                <x-icon name="chevron-right" size="size-3.5" />
                <span class="text-primary-600">Profil Saya</span>
            </div>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Profil Saya</h1>
            <p class="mt-2 text-sm text-slate-500">Kelola informasi akun, data profil, dan avatar yang tampil di aplikasi.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="grid gap-5">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <p class="font-semibold">Periksa kembali data yang diisi.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-card>
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                <div class="grid size-24 shrink-0 place-items-center">
                    @if ($user->avatar_path)
                        <img data-profile-avatar-preview src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar_path) }}" alt="Avatar {{ $user->name }}" class="size-24 rounded-full object-cover ring-4 ring-primary-50">
                        <span data-profile-avatar-fallback class="hidden size-24 place-items-center rounded-full bg-primary-50 text-2xl font-bold text-primary-700 ring-4 ring-primary-50">{{ $userInitials }}</span>
                    @else
                        <img data-profile-avatar-preview alt="Preview avatar {{ $user->name }}" class="hidden size-24 rounded-full object-cover ring-4 ring-primary-50">
                        <span data-profile-avatar-fallback class="grid size-24 place-items-center rounded-full bg-primary-50 text-2xl font-bold text-primary-700 ring-4 ring-primary-50">{{ $userInitials }}</span>
                    @endif
                </div>
                <div class="grid gap-2">
                    <label class="text-sm font-semibold text-slate-800" for="avatar">Avatar</label>
                    <input id="avatar" data-profile-avatar-input type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="rounded-lg border border-line px-3 py-2.5 text-sm font-normal file:mr-3 file:rounded-md file:border-0 file:bg-primary-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-primary-700">
                    <p class="text-xs text-slate-500">JPG, PNG, atau WEBP. Maksimal 2 MB. Preview tampil setelah memilih file.</p>
                    @error('avatar')<span class="text-xs font-normal text-red-600">{{ $message }}</span>@enderror
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="mb-5">
                <h2 class="text-base font-semibold text-slate-900">Informasi akun</h2>
                <p class="mt-1 text-sm text-slate-500">Data yang digunakan untuk masuk ke aplikasi.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-2 text-sm font-semibold text-slate-700">
                    <span>Nama lengkap <span class="text-danger">*</span></span>
                    <input name="name" value="{{ old('name', $user->name) }}" required maxlength="150" class="h-11 rounded-lg border border-line px-3 font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100">
                    @error('name')<span class="text-xs font-normal text-red-600">{{ $message }}</span>@enderror
                </label>
                <label class="grid gap-2 text-sm font-semibold text-slate-700">
                    <span>Username @if ($isEmployee)<span class="font-normal text-slate-400">(mengikuti ID karyawan)</span>@endif</span>
                    <input name="username" value="{{ old('username', $user->username) }}" maxlength="100" @readonly($isEmployee) class="h-11 rounded-lg border border-line px-3 font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100 @if ($isEmployee) bg-slate-50 text-slate-500 @endif">
                    @error('username')<span class="text-xs font-normal text-red-600">{{ $message }}</span>@enderror
                </label>
                <label class="grid gap-2 text-sm font-semibold text-slate-700 sm:col-span-2">
                    <span>Email</span>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" maxlength="150" class="h-11 rounded-lg border border-line px-3 font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100">
                    @error('email')<span class="text-xs font-normal text-red-600">{{ $message }}</span>@enderror
                </label>
            </div>
        </x-card>

        @if ($isEmployee)
            <x-card>
                <div class="mb-5">
                    <h2 class="text-base font-semibold text-slate-900">Data karyawan</h2>
                    <p class="mt-1 text-sm text-slate-500">Data identitas dan kepegawaian. Field yang bersifat administrasi dikelola oleh administrator.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        <span>ID karyawan</span>
                        <input value="{{ $employee->employee_no }}" readonly class="h-11 rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-500">
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        <span>SIM ID</span>
                        <input name="sim_id" value="{{ old('sim_id', $employee->sim_id) }}" maxlength="100" class="h-11 rounded-lg border border-line px-3 font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100">
                        @error('sim_id')<span class="text-xs font-normal text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        <span>Nomor telepon <span class="text-danger">*</span></span>
                        <input name="phone" value="{{ old('phone', $employee->phone) }}" required maxlength="30" class="h-11 rounded-lg border border-line px-3 font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100">
                        @error('phone')<span class="text-xs font-normal text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        <span>Tanggal masuk</span>
                        <input value="{{ $employee->join_date ? date('d/m/Y', strtotime($employee->join_date)) : '—' }}" readonly class="h-11 rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-500">
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        <span>Jenis kelamin</span>
                        <input value="{{ match ($employee->gender) { 'male' => 'Laki-laki', 'female' => 'Perempuan', default => '—' } }}" readonly class="h-11 rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-500">
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        <span>Status karyawan</span>
                        <input value="{{ match ($employee->employee_status) { 'permanent' => 'Tetap', 'contract' => 'Kontrak', 'daily' => 'Harian', default => '—' } }}" readonly class="h-11 rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-500">
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        <span>Status perkawinan <span class="text-danger">*</span></span>
                        <select name="marital_status" required class="h-11 rounded-lg border border-line bg-white px-3 font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100">
                            <option value="single" @selected(old('marital_status', $employee->marital_status) === 'single')>Belum menikah</option>
                            <option value="married" @selected(old('marital_status', $employee->marital_status) === 'married')>Menikah</option>
                            <option value="divorced" @selected(old('marital_status', $employee->marital_status) === 'divorced')>Cerai hidup</option>
                            <option value="widowed" @selected(old('marital_status', $employee->marital_status) === 'widowed')>Cerai mati</option>
                        </select>
                        @error('marital_status')<span class="text-xs font-normal text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        <span>Group</span>
                        <input value="{{ $employee->group?->name ?? 'Tanpa group' }}" readonly class="h-11 rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-500">
                    </label>
                </div>
            </x-card>
        @elseif ($isClient)
            <x-card>
                <div class="mb-5">
                    <h2 class="text-base font-semibold text-slate-900">Data client</h2>
                    <p class="mt-1 text-sm text-slate-500">Informasi client aktif. Perubahan data master dilakukan oleh administrator.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        <span>Kode client</span>
                        <input value="{{ $currentClient->code }}" readonly class="h-11 rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-500">
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        <span>Nama client</span>
                        <input value="{{ $currentClient->name }}" readonly class="h-11 rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-500">
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        <span>Timezone</span>
                        <input value="{{ $currentClient->timezone }}" readonly class="h-11 rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-500">
                    </label>
                    <label class="grid gap-2 text-sm font-semibold text-slate-700">
                        <span>Status client</span>
                        <input value="{{ $currentClient->status === 'active' ? 'Aktif' : 'Nonaktif' }}" readonly class="h-11 rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-500">
                    </label>
                </div>
            </x-card>
        @endif

        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
                <x-icon name="check-circle" size="size-4" /> Simpan profil
            </button>
        </div>
    </form>
</x-layouts.app>
