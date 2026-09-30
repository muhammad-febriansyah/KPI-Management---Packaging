<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->updateOrInsert(
            ['code' => 'admin'],
            ['name' => 'Admin', 'description' => 'Administrator terbatas pada client yang ditugaskan.', 'updated_at' => now(), 'created_at' => now()],
        );

        $roleId = DB::table('roles')->where('code', 'admin')->value('id');
        $menus = [
            'dashboard' => 'Dashboard',
            'units' => 'Satuan',
            'groups' => 'Group',
            'cost-centers' => 'Cost Center',
            'products' => 'Produk',
            'employees' => 'Karyawan',
            'realizations' => 'Realisasi',
            'deductions' => 'Potongan Gaji',
            'work-reports' => 'Hasil Pekerjaan',
            'reports' => 'Laporan Gaji',
            'settings' => 'User & Hak Akses',
        ];

        foreach ($menus as $key => $label) {
            DB::table('permissions')->updateOrInsert(
                ['code' => 'menu.'.$key],
                ['name' => 'Menu: '.$label, 'updated_at' => now(), 'created_at' => now()],
            );
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('code', array_map(fn (string $key): string => 'menu.'.$key, array_keys($menus)))
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        $roleId = DB::table('roles')->where('code', 'admin')->value('id');

        if ($roleId !== null) {
            DB::table('permission_role')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }
};
