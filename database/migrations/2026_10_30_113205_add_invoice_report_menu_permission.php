<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permission = Permission::query()->firstOrCreate(
            ['code' => 'menu.invoice-report'],
            ['name' => 'Menu: Report Invoice'],
        );

        foreach (['admin', 'client'] as $roleCode) {
            Role::query()->where('code', $roleCode)->first()?->permissions()->syncWithoutDetaching([$permission->getKey()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = Permission::query()->where('code', 'menu.invoice-report')->first();

        if ($permission === null) {
            return;
        }

        $permission->roles()->detach();
        $permission->delete();
    }
};
