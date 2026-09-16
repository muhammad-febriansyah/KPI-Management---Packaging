<?php

use App\Models\Client;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function attachRole(User $user, Client $client, string $roleCode): Role
{
    $role = Role::query()->firstOrCreate(['code' => $roleCode], ['name' => ucfirst($roleCode)]);
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);

    return $role;
}

it('rejects a non super admin from viewing the settings/access page', function () {
    $client = Client::factory()->create();
    $user = User::factory()->create();
    attachRole($user, $client, 'client');

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('settings.access'));

    $response->assertForbidden();
});

it('lets a super admin view the settings/access page', function () {
    $client = Client::factory()->create();
    $admin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('settings.access'));

    $response->assertOk()
        ->assertSee('User & Hak Akses')
        ->assertSee('data-access-tab="users"', false)
        ->assertSee('data-access-tab="permissions"', false)
        ->assertSee('data-access-panel="users"', false)
        ->assertSee('data-access-panel="permissions"', false);
});

it('lets a super admin toggle a client user status', function () {
    $client = Client::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create(['name' => 'User Uji']);
    attachRole($user, $client, 'client');

    $response = $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('settings.access.users.status', $user));

    $response->assertOk()->assertJsonPath('status', 'inactive');
    $this->assertDatabaseHas('users', ['id' => $user->getKey(), 'status' => 'inactive']);
    $this->assertDatabaseHas('client_user', ['client_id' => $client->getKey(), 'user_id' => $user->getKey(), 'status' => 'inactive']);

    $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('settings.access.users.status', $user))
        ->assertOk()
        ->assertJsonPath('status', 'active');
});

it('rejects a non super admin from toggling a user status', function () {
    $client = Client::factory()->create();
    $user = User::factory()->create();
    attachRole($user, $client, 'client');

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('settings.access.users.status', $user));

    $response->assertForbidden();
});

it('lets a super admin update which menus a role can see', function () {
    $admin = User::factory()->superAdmin()->create();
    $role = Role::factory()->create(['code' => 'client']);
    Permission::query()->create(['code' => 'menu.dashboard', 'name' => 'Menu: Dashboard']);
    $productsPermission = Permission::query()->create(['code' => 'menu.products', 'name' => 'Menu: Produk']);
    $role->permissions()->attach($productsPermission);

    $response = $this->actingAs($admin)->putJson(route('settings.access.roles.update', $role), [
        'menus' => ['dashboard'],
    ]);

    $response->assertOk()->assertJsonPath('message', 'Hak akses menu untuk role '.$role->name.' berhasil diperbarui.');
    expect($role->permissions()->pluck('code')->all())->toBe(['menu.dashboard']);
});

it('rejects an unknown menu key when updating role permissions', function () {
    $admin = User::factory()->superAdmin()->create();
    $role = Role::factory()->create(['code' => 'client']);

    $response = $this->actingAs($admin)->putJson(route('settings.access.roles.update', $role), [
        'menus' => ['not-a-real-menu'],
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors(['menus.0']);
});

it('rejects a non super admin from updating role permissions', function () {
    $client = Client::factory()->create();
    $user = User::factory()->create();
    attachRole($user, $client, 'client');
    $role = Role::factory()->create(['code' => 'employee']);

    $response = $this->actingAs($user)->putJson(route('settings.access.roles.update', $role), [
        'menus' => ['dashboard'],
    ]);

    $response->assertForbidden();
});

it('forbids editing the super-admin role\'s menus, even as a super admin', function () {
    $admin = User::factory()->superAdmin()->create();
    $superAdminRole = Role::query()->create(['code' => 'super-admin', 'name' => 'Super Admin']);

    $response = $this->actingAs($admin)->putJson(route('settings.access.roles.update', $superAdminRole), [
        'menus' => ['dashboard'],
    ]);

    $response->assertForbidden();
});
