<?php

use App\Models\Client;
use App\Models\Employee;
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
        ->assertSee('data-access-panel="permissions"', false)
        ->assertSee('>Username<', false)
        ->assertSee('data-server-table', false);
});

it('shows the active client selector so super admins can find accounts created for another client', function () {
    $currentClient = Client::factory()->create(['code' => 'CURRENT-001', 'name' => 'PT Current']);
    $otherClient = Client::factory()->create(['code' => 'OTHER-001', 'name' => 'PT Other']);
    $admin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($admin)
        ->withSession(['current_client_id' => $currentClient->getKey()])
        ->get(route('settings.access'));

    $response->assertOk()
        ->assertSee('data-client-switcher', false)
        ->assertSee('data-select2-select', false)
        ->assertSee('data-select2-placeholder="Cari kode atau nama client..."', false)
        ->assertSee('name="client_id"', false)
        ->assertSee($currentClient->name)
        ->assertSee($otherClient->name);
});

it('renders unified role form and reset password modal on settings/access', function () {
    $client = Client::factory()->create();
    $admin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($admin)
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
        ->assertSee('name="employee_id"', false)
        ->assertSee('data-tom-select-remote="'.route('settings.access.employee-options').'"', false)
        ->assertSee('Jika karyawan sudah memiliki akun, pilihannya akan memunculkan peringatan dan tidak dapat dibuat ulang.')
        ->assertSee('name="client_id"', false)
        ->assertSee('data-tom-select-remote="'.route('settings.access.client-options').'"', false)
        ->assertSee('data-super-admin-form', false)
        ->assertSee('data-user-reset-form', false);

    expect(substr_count($response->getContent(), 'data-password-toggle'))->toBe(12);
});

it('searches active master employees for employee account creation', function () {
    $client = Client::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $employee = Employee::factory()->create([
        'client_id' => $client->getKey(),
        'user_id' => null,
        'full_name' => 'Karyawan Belum Login',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('settings.access.employee-options', ['q' => 'Belum Login']));

    $response->assertOk()->assertJsonPath('results.0.id', $employee->getKey())->assertJsonPath('results.0.name', 'Karyawan Belum Login');
});

it('returns employees with existing accounts so the form can warn before duplicate creation', function () {
    $client = Client::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $employeeUser = User::factory()->create(['name' => 'Agus Setiawan', 'username' => 'EMP000006']);
    $employee = Employee::factory()->create([
        'client_id' => $client->getKey(),
        'user_id' => $employeeUser->getKey(),
        'full_name' => 'Agus Setiawan',
        'status' => 'active',
    ]);

    $response = $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('settings.access.employee-options', ['q' => 'Agu']));

    $response->assertOk()
        ->assertJsonPath('results.0.id', $employee->getKey())
        ->assertJsonPath('results.0.has_account', true);
});

it('creates an account for an existing master employee', function () {
    $client = Client::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => null]);

    $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('settings.access.employee-accounts.store'), [
            'employee_id' => $employee->getKey(),
            'email' => 'employee.account@example.com',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])
        ->assertCreated();

    $employee->refresh();
    $this->assertDatabaseHas('users', [
        'id' => $employee->user_id,
        'name' => $employee->full_name,
        'username' => $employee->employee_no,
        'email' => 'employee.account@example.com',
    ]);
    $this->assertDatabaseHas('client_user', [
        'client_id' => $client->getKey(),
        'user_id' => $employee->user_id,
        'status' => 'active',
    ]);
    expect(Hash::check('password-baru', User::query()->findOrFail($employee->user_id)->password))->toBeTrue();
});

it('rejects creating a second account for an employee', function () {
    $client = Client::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $employeeUser = User::factory()->create();
    $employee = Employee::factory()->create([
        'client_id' => $client->getKey(),
        'user_id' => $employeeUser->getKey(),
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('settings.access.employee-accounts.store'), [
            'employee_id' => $employee->getKey(),
            'email' => 'duplicate.employee@example.com',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['employee_id']);
});

it('searches active master clients for client account creation', function () {
    $currentClient = Client::factory()->create();
    $targetClient = Client::factory()->create(['code' => 'TARGET-001', 'name' => 'PT Target Account']);
    $admin = User::factory()->superAdmin()->create();
    $existingAccount = User::factory()->create();
    attachRole($existingAccount, $targetClient, 'client');

    $response = $this->actingAs($admin)
        ->withSession(['current_client_id' => $currentClient->getKey()])
        ->getJson(route('settings.access.client-options', ['q' => 'Target']));

    $response->assertOk()->assertJsonPath('results.0.id', $targetClient->getKey())->assertJsonPath('results.0.code', 'TARGET-001');
});

it('creates a client account for an existing master client', function () {
    $currentClient = Client::factory()->create();
    $targetClient = Client::factory()->create();
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->withSession(['current_client_id' => $currentClient->getKey()])
        ->postJson(route('settings.access.client-accounts.store'), [
            'client_id' => $targetClient->getKey(),
            'account_name' => 'PIC Target',
            'login_username' => 'pic.target',
            'login_email' => 'pic.target@example.com',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])
        ->assertCreated();

    $account = User::query()->where('username', 'pic.target')->firstOrFail();
    $this->assertDatabaseHas('client_user', [
        'client_id' => $targetClient->getKey(),
        'user_id' => $account->getKey(),
        'status' => 'active',
    ]);
    expect(Hash::check('password-baru', $account->password))->toBeTrue();
});

it('renders distinct user actions with icons', function () {
    $client = Client::factory()->create();
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create(['name' => 'Akun Karyawan']);
    attachRole($user, $client, 'employee');
    Employee::factory()->create([
        'client_id' => $client->getKey(),
        'user_id' => $user->getKey(),
        'full_name' => 'Akun Karyawan',
    ]);

    $response = $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('settings.access', ['draw' => 1, 'start' => 0, 'length' => 10]));

    $action = collect($response->json('data'))->firstWhere('name', 'Akun Karyawan')['action'];
    $row = collect($response->json('data'))->firstWhere('name', 'Akun Karyawan');
    expect($row['role_name'])
        ->toContain('border-sky-200')
        ->toContain('bg-sky-50')
        ->toContain('Karyawan');
    expect($action)
        ->toContain('bg-sky-50')
        ->toContain('bg-amber-50')
        ->toContain('bg-red-50')
        ->toContain('bg-rose-50')
        ->toContain('heroicons.svg#pencil')
        ->toContain('heroicons.svg#lock-closed')
        ->toContain('heroicons.svg#x-mark')
        ->toContain('heroicons.svg#trash');
});

it('allows a super admin to create and update another super admin', function () {
    $admin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($admin)->postJson(route('settings.access.super-admins.store'), [
        'name' => 'Admin Presentasi',
        'username' => 'admin.presentasi',
        'email' => 'admin.presentasi@example.com',
        'password' => 'password-baru',
        'password_confirmation' => 'password-baru',
    ]);

    $response->assertCreated();
    $created = User::query()->where('username', 'admin.presentasi')->firstOrFail();
    expect($created->is_super_admin)->toBeTrue()->and($created->status)->toBe('active');

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
