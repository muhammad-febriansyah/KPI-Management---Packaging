<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['code' => 'menu.shifts'],
            ['name' => 'Menu: Master Shift', 'updated_at' => now(), 'created_at' => now()],
        );

        $roleId = DB::table('roles')->where('code', 'admin')->value('id');
        $permissionId = DB::table('permissions')->where('code', 'menu.shifts')->value('id');

        if ($roleId !== null && $permissionId !== null) {
            DB::table('permission_role')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('code', 'menu.shifts')->value('id');

        if ($permissionId !== null) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
