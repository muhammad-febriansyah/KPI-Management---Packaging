@if ($group === null && $user->is_super_admin)
    <label class="grid max-w-xl gap-2 text-sm font-semibold text-slate-700">
        <span>Client <span class="text-danger">*</span></span>
        <select name="client_id" data-select2-select data-select2-placeholder="Pilih client..." required class="h-11 w-full rounded-lg border border-line bg-white px-3 font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100">
            <option value="" disabled @selected(old('client_id', $currentClient->getKey()) === '')>Pilih client...</option>
            @foreach ($availableClients as $clientOption)
                <option value="{{ $clientOption->getKey() }}" @selected((int) old('client_id', $currentClient->getKey()) === $clientOption->getKey())>{{ $clientOption->code }} — {{ $clientOption->name }}</option>
            @endforeach
        </select>
        @error('client_id')<span class="text-xs font-normal text-red-600">{{ $message }}</span>@enderror
    </label>
@else
    <label class="grid max-w-xl gap-2 text-sm font-semibold text-slate-700">
        <span>Client</span>
        <div class="flex h-11 items-center rounded-lg border border-line bg-slate-50 px-3 font-normal text-slate-600">{{ $currentClient->code }} — {{ $currentClient->name }}</div>
        <small class="font-normal text-slate-500">Group ini hanya berlaku untuk client tersebut.</small>
    </label>
@endif

<div class="grid gap-5 sm:grid-cols-2">
    <label class="grid gap-2 text-sm font-semibold text-slate-700">
        <span>Kode group <span class="font-normal text-slate-500">(opsional)</span></span>
        <input name="code" value="{{ old('code', $group?->code) }}" maxlength="50" placeholder="Contoh: PROD-A" class="h-11 rounded-lg border border-line px-3 font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100">
        @error('code')<span class="text-xs font-normal text-red-600">{{ $message }}</span>@enderror
    </label>
    <label class="grid gap-2 text-sm font-semibold text-slate-700">
        <span>Nama group <span class="text-danger">*</span></span>
        <input name="name" value="{{ old('name', $group?->name) }}" required maxlength="100" placeholder="Masukkan nama group" class="h-11 rounded-lg border border-line px-3 font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100">
        @error('name')<span class="text-xs font-normal text-red-600">{{ $message }}</span>@enderror
    </label>
</div>

<label class="grid max-w-sm gap-2 text-sm font-semibold text-slate-700">
    <span>Status <span class="text-danger">*</span></span>
    <select name="status" required class="h-11 rounded-lg border border-line bg-white px-3 font-normal outline-none focus:border-primary-600 focus:ring-3 focus:ring-primary-100">
        <option value="" disabled @selected(old('status', $group?->status) === null)>Pilih status...</option>
        <option value="active" @selected(old('status', $group?->status ?? 'active') === 'active')>Aktif</option>
        <option value="inactive" @selected(old('status', $group?->status) === 'inactive')>Nonaktif</option>
    </select>
    @error('status')<span class="text-xs font-normal text-red-600">{{ $message }}</span>@enderror
</label>
