<x-layouts.app title="User & Hak Akses" active="settings" :current-client="$currentClient" :user="$user">
<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"><div><p class="text-xs text-slate-500">Pengaturan / Akses</p><h1 class="mt-2 text-2xl font-semibold text-slate-950">User & Hak Akses</h1><p class="mt-1 text-sm text-slate-500">Kelola pengguna, role, dan hak akses aplikasi.</p></div><button type="button" data-access-create class="inline-flex h-11 items-center gap-2 self-start rounded-lg bg-primary-600 px-4 text-sm font-semibold text-white hover:bg-primary-700 lg:self-auto"><x-icon name="plus" size="size-4" /> Tambah User</button></div>
<div data-access-tabs>
<div class="mb-6" role="tablist" aria-label="Pengaturan akses">
<div class="inline-flex max-w-full gap-1 overflow-x-auto rounded-xl bg-slate-100 p-1">
<button type="button" id="access-tab-users" role="tab" data-access-tab="users" aria-controls="access-panel-users" aria-selected="true" class="rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-primary-600 shadow-sm ring-1 ring-slate-200/70">Data User</button>
<button type="button" id="access-tab-permissions" role="tab" data-access-tab="permissions" aria-controls="access-panel-permissions" aria-selected="false" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-500 transition hover:text-slate-700">Hak Akses</button>
</div>
</div>
<section id="access-panel-users" data-access-panel="users" role="tabpanel" aria-labelledby="access-tab-users" tabindex="0">
<x-card :padding="false"><div class="overflow-x-auto"><table data-server-table class="min-w-[980px] w-full text-left text-sm"><thead><tr><th>Nama</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Aksi</th></tr></thead><tbody></tbody></table></div></x-card>
</section>
<section id="access-panel-permissions" data-access-panel="permissions" role="tabpanel" aria-labelledby="access-tab-permissions" tabindex="0" hidden>
<div><h2 class="text-lg font-semibold text-slate-950">Hak Akses Menu per Role</h2><p class="mt-1 mb-4 text-sm text-slate-500">Centang menu yang boleh diakses tiap role. Super Admin selalu memiliki akses penuh.</p>
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
</section>
</div>
@include('employees._modal', ['groups' => $groups])
@include('clients._modal')
@include('settings._user-create-modal', ['groups' => $groups])
@include('settings._super-admin-modal')
@include('settings._reset-password-modal')
</x-layouts.app>
