<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuthorizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'super-admin' => 'Super Admin PT SIM',
            'employee' => 'Karyawan',
            'client' => 'Client',
        ];

        foreach ($roles as $code => $name) {
            Role::query()->updateOrCreate(['code' => $code], ['name' => $name]);
        }

        $employeeRole = Role::query()->where('code', 'employee')->value('id');
        $legacyRoles = Role::query()->whereIn('code', ['admin-cro', 'supervisor', 'operator', 'client-viewer'])->pluck('id');
        DB::table('client_user')->whereIn('role_id', $legacyRoles)->update(['role_id' => $employeeRole]);
        Role::query()->whereIn('id', $legacyRoles)->delete();

        $permissions = [
            'clients' => ['view', 'create', 'update', 'delete'],
            'users' => ['view', 'create', 'update', 'delete'],
            'groups' => ['view', 'create', 'update', 'delete'],
            'units' => ['view', 'create', 'update', 'delete'],
            'cost-centers' => ['view', 'create', 'update', 'delete'],
            'shifts' => ['view', 'create', 'update', 'delete'],
            'batches' => ['view', 'create', 'update', 'delete'],
            'employees' => ['view', 'create', 'update', 'delete'],
            'products' => ['view', 'create', 'update', 'delete'],
            'realizations' => ['view', 'create', 'update', 'delete', 'finalize', 'cancel'],
            'deductions' => ['view', 'create', 'update', 'delete', 'lock', 'unlock'],
            'reports' => ['view', 'export'],
            'dashboard' => ['view'],
            'audit-logs' => ['view'],
        ];

        foreach ($permissions as $resource => $actions) {
            foreach ($actions as $action) {
                $code = $resource.'.'.$action;

                Permission::query()->updateOrCreate(['code' => $code], ['name' => $code]);
            }
        }

        // Sidebar menu visibility per role, managed by Super Admin via Settings > User & Hak Akses.
        foreach (config('menu_permissions') as $key => $label) {
            Permission::query()->updateOrCreate(['code' => 'menu.'.$key], ['name' => 'Menu: '.$label]);
        }

        $defaultRoleMenus = [
            'employee' => ['dashboard', 'realizations'],
            'client' => ['dashboard', 'products', 'realizations', 'deductions', 'reports', 'work-reports'],
        ];

        foreach ($defaultRoleMenus as $code => $menuKeys) {
            $role = Role::query()->where('code', $code)->first();

            // Skip roles that already have menus configured, so re-seeding never
            // clobbers menu access a Super Admin has since customized.
            if (! $role || $role->permissions()->where('code', 'like', 'menu.%')->exists()) {
                continue;
            }

            $permissionIds = Permission::query()
                ->whereIn('code', array_map(fn (string $key): string => 'menu.'.$key, $menuKeys))
                ->pluck('id');

            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
