<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('roles')->updateOrInsert(
            ['code' => 'leader'],
            ['name' => 'Leader', 'description' => 'Leader dengan akses realisasi dan gaji sendiri.', 'updated_at' => now(), 'created_at' => now()],
        );
        $leaderRoleId = DB::table('roles')->where('code', 'leader')->value('id');

        DB::table('permissions')->updateOrInsert(
            ['code' => 'menu.my-payroll'],
            ['name' => 'Menu: Gaji Saya', 'updated_at' => now(), 'created_at' => now()],
        );

        $permissionIds = DB::table('permissions')
            ->whereIn('code', ['menu.realizations', 'menu.my-payroll'])
            ->pluck('id');

        DB::table('permission_role')->where('role_id', $leaderRoleId)->delete();
        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $leaderRoleId,
            ]);
        }

        $employeeRoleId = DB::table('roles')->where('code', 'employee')->value('id');
        if ($employeeRoleId !== null) {
            DB::table('permission_role')
                ->where('role_id', $employeeRoleId)
                ->whereIn('permission_id', DB::table('permissions')->where('code', 'like', 'menu.%')->pluck('id'))
                ->delete();

            $myPayrollPermissionId = DB::table('permissions')->where('code', 'menu.my-payroll')->value('id');
            DB::table('permission_role')->insertOrIgnore([
                'permission_id' => $myPayrollPermissionId,
                'role_id' => $employeeRoleId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $leaderRoleId = DB::table('roles')->where('code', 'leader')->value('id');

        if ($leaderRoleId !== null) {
            DB::table('permission_role')->where('role_id', $leaderRoleId)->delete();
            DB::table('roles')->where('id', $leaderRoleId)->delete();
        }

        DB::table('permissions')->where('code', 'menu.my-payroll')->delete();
    }
};
