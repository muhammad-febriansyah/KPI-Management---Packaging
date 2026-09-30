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
            ['code' => 'menu.invoices'],
            ['name' => 'Menu: Invoice Borongan'],
        );

        foreach (['admin', 'leader'] as $roleCode) {
            $role = Role::query()->where('code', $roleCode)->first();

            $role?->permissions()->syncWithoutDetaching([$permission->getKey()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = Permission::query()->where('code', 'menu.invoices')->first();

        if ($permission === null) {
            return;
        }

        $permission->roles()->detach();
        $permission->delete();
    }
};
