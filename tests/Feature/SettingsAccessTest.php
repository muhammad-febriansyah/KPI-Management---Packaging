<?php

use App\Models\Client;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

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

it('renders unified role form and reset password modal on settings/access', function () {
    $client = Client::factory()->create();
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('settings.access'))
        ->assertOk()
        ->assertSee('data-access-create', false)
        ->assertSee('data-user-create-modal', false)
        ->assertSee('data-user-create-form', false)
        ->assertSee('Role user')
        ->assertSee('name="create_role"', false)
        ->assertSee('value="super-admin"', false)
        ->assertSee('value="employee"', false)
        ->assertSee('value="client"', false)
        ->assertSee('value="active" selected', false)
        ->assertSee('data-super-admin-form', false)
        ->assertSee('data-user-reset-form', false);
});

it('allows a super admin to create and update another super admin', function () {
    $admin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($admin)->postJson(route('settings.access.super-admins.store'), [
        'name' => 'Admin Presentasi',
        'username' => 'admin.presentasi',
        'email' => 'admin.presentasi@example.com',
        'status' => 'active',
        'password' => 'password-baru',
        'password_confirmation' => 'password-baru',
    ]);

    $response->assertCreated();
    $created = User::query()->where('username', 'admin.presentasi')->firstOrFail();
    expect($created->is_super_admin)->toBeTrue();

    $this->actingAs($admin)->putJson(route('settings.access.super-admins.update', $created), [
        'name' => 'Admin Presentasi Updated',
        'username' => 'admin.presentasi.updated',
        'email' => 'admin.presentasi.updated@example.com',
        'status' => 'inactive',
    ])->assertOk();

    $this->assertDatabaseHas('users', [
        'id' => $created->getKey(),
        'name' => 'Admin Presentasi Updated',
        'username' => 'admin.presentasi.updated',
        'status' => 'inactive',
        'is_super_admin' => true,
    ]);
});

it('allows a super admin to reset password for a user in current client', function () {
    $client = Client::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();
    attachRole($user, $client, 'client');

    $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('settings.access.users.password', $user), [
            'password' => 'password-reset',
            'password_confirmation' => 'password-reset',
        ])
        ->assertOk();

    expect(Hash::check('password-reset', $user->fresh()->password))->toBeTrue();
});

it('protects the last super admin and deletes a scoped user', function () {
    $client = Client::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();
    attachRole($user, $client, 'client');

    $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->deleteJson(route('settings.access.users.destroy', $user))
        ->assertOk();

    $this->assertDatabaseMissing('users', ['id' => $user->getKey()]);

    $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->deleteJson(route('settings.access.users.destroy', $admin))
        ->assertUnprocessable();
});

it('updates selected client account when client form is opened from user access', function () {
    $client = Client::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $defaultAccount = User::factory()->create(['name' => 'PIC Lama', 'username' => 'pic.lama', 'email' => 'pic.lama@example.com']);
    $selectedAccount = User::factory()->create(['name' => 'PIC Dipilih', 'username' => 'pic.dipilih', 'email' => 'pic.dipilih@example.com']);
    attachRole($defaultAccount, $client, 'client');
    attachRole($selectedAccount, $client, 'client');

    $this->actingAs($admin)
        ->putJson(route('clients.update', $client).'?account_user_id='.$selectedAccount->getKey(), [
            'code' => $client->code,
            'name' => $client->name,
            'account_name' => 'PIC Dipilih Updated',
            'login_username' => 'pic.dipilih.updated',
            'login_email' => 'pic.dipilih.updated@example.com',
            'status' => 'active',
        ])
        ->assertOk();

    $this->assertDatabaseHas('users', [
        'id' => $selectedAccount->getKey(),
        'name' => 'PIC Dipilih Updated',
        'username' => 'pic.dipilih.updated',
    ]);
    $this->assertDatabaseHas('users', [
        'id' => $defaultAccount->getKey(),
        'name' => 'PIC Lama',
        'username' => 'pic.lama',
    ]);
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
