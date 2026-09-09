<x-layouts.app title="User & Hak Akses" active="settings" :current-client="$currentClient" :user="$user">
<div class="mb-6"><p class="text-xs text-slate-500">Pengaturan / Akses</p><h1 class="mt-2 text-2xl font-semibold text-slate-950">User & Hak Akses</h1><p class="mt-1 text-sm text-slate-500">Kelola pengguna, role, dan hak akses aplikasi.</p></div>
<x-card :padding="false"><table data-server-table class="w-full text-left text-sm"><thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th></tr></thead><tbody></tbody></table></x-card>
{{-- Hak Akses Menu per Role — hidden for now (not deleted, just paused). Backend
enforcement is also paused (see User::allowedMenuKeys()/canAccessMenu()) — every
role sees the full sidebar and every menu-gated page, same as before this feature
existed. --}}
{{--
<div class="mt-8"><h2 class="text-lg font-semibold text-slate-950">Hak Akses Menu per Role</h2><p class="mt-1 mb-4 text-sm text-slate-500">Centang menu yang boleh diakses tiap role. Super Admin selalu memiliki akses penuh.</p>
<div class="grid gap-4 lg:grid-cols-2">
@foreach ($roles as $role)
<x-card :title="$role['name']"><form data-role-access-form data-role-id="{{ $role['id'] }}" class="grid gap-4"><div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
@foreach ($menuOptions as $key => $label)
<label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="menus[]" value="{{ $key }}" class="size-4 rounded border-line accent-primary-600" @checked(in_array($key, $role['menus'], true))> {{ $label }}</label>
@endforeach
</div><div class="flex justify-end"><x-button type="submit" size="sm">Simpan</x-button></div></form></x-card>
@endforeach
</div>
</div>
--}}
</x-layouts.app>
