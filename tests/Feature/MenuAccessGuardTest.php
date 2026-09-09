<?php

use App\Models\Client;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function guardTestRole(string $code, string $name, array $menuKeys = []): Role
{
    $role = Role::query()->firstOrCreate(['code' => $code], ['name' => $name]);
    $permissionIds = collect($menuKeys)->map(fn (string $key): int => Permission::query()->firstOrCreate(['code' => 'menu.'.$key], ['name' => 'Menu: '.$key])->getKey());
    $role->permissions()->syncWithoutDetaching($permissionIds);

    return $role;
}

function guardTestUser(Client $client, Role $role): User
{
    $user = User::factory()->create();
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);

    return $user;
}

it('blocks a role with no menus granted from every menu-gated page', function (string $path) {
    $client = Client::factory()->create();
    $role = guardTestRole('bare', 'Bare Role');
    $user = guardTestUser($client, $role);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->get($path);

    $response->assertForbidden();
})->with([
    '/products' => '/products',
    '/employees' => '/employees',
    '/deductions' => '/deductions',
    '/reports/payroll' => '/reports/payroll',
    '/reports/work' => '/reports/work',
])->skip('Menu access enforcement is paused — see User::canAccessMenu().');

it('lets a role in once its matching menu is granted', function (string $menuKey, string $path) {
    $client = Client::factory()->create();
    $role = guardTestRole('granted', 'Granted Role', [$menuKey]);
    $user = guardTestUser($client, $role);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->get($path);

    $response->assertOk();
})->with([
    'products' => ['products', '/products'],
    'employees' => ['employees', '/employees'],
    'deductions' => ['deductions', '/deductions'],
    'reports' => ['reports', '/reports/payroll'],
    'work-reports' => ['work-reports', '/reports/work'],
]);

it('never exposes master client management or the audit log outside super admin', function (string $path) {
    $client = Client::factory()->create();
    $role = guardTestRole('full', 'Full Role', ['dashboard', 'products', 'employees', 'realizations', 'deductions', 'work-reports', 'reports']);
    $user = guardTestUser($client, $role);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->get($path);

    $response->assertForbidden();
})->with([
    '/clients' => '/clients',
    '/audit-logs' => '/audit-logs',
    '/shifts' => '/shifts',
]);

it('lets a super admin reach every menu-gated and admin-only page regardless of any menu grant', function (string $path) {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($admin)->withSession(['current_client_id' => $client->getKey()])->get($path);

    $response->assertOk();
})->with([
    '/products' => '/products',
    '/employees' => '/employees',
    '/deductions' => '/deductions',
    '/reports/payroll' => '/reports/payroll',
    '/reports/work' => '/reports/work',
    '/clients' => '/clients',
    '/audit-logs' => '/audit-logs',
    '/shifts' => '/shifts',
]);

it('gates the product form quick-manage endpoints behind the products menu', function (string $path) {
    $client = Client::factory()->create();
    $bare = guardTestRole('bare-units', 'Bare Units Role');
    $bareUser = guardTestUser($client, $bare);

    $this->actingAs($bareUser)->withSession(['current_client_id' => $client->getKey()])->get($path)->assertForbidden();

    $granted = guardTestRole('granted-units', 'Granted Units Role', ['products']);
    $grantedUser = guardTestUser($client, $granted);

    $this->actingAs($grantedUser)->withSession(['current_client_id' => $client->getKey()])->get($path)->assertOk();
})->with([
    '/units' => '/units',
    '/groups' => '/groups',
    '/cost-centers' => '/cost-centers',
])->skip('Menu access enforcement is paused — see User::canAccessMenu().');
