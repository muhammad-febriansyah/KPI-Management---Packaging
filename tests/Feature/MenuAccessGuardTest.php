<?php

use App\Models\Client;
use App\Models\Permission;
use App\Models\Product;
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
]);

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
]);

it('blocks direct mutation endpoints when the matching menu is not granted', function () {
    $client = Client::factory()->create();
    $role = guardTestRole('bare-mutations', 'Bare Mutations Role');
    $user = guardTestUser($client, $role);

    $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->postJson(route('products.store'), [
        'client_id' => $client->getKey(),
        'sku' => 'NO-MENU-PRODUCT',
        'name' => 'Tidak boleh',
        'status' => 'active',
    ])->assertForbidden();

    $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->postJson(route('employees.store'), [
        'full_name' => 'Tidak boleh',
        'email' => 'blocked@example.com',
        'phone' => '081234567890',
        'join_date' => '2026-01-01',
        'gender' => 'male',
        'employee_status' => 'permanent',
        'marital_status' => 'single',
    ])->assertForbidden();

    $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->postJson(route('groups.store'), [
        'name' => 'Tidak boleh',
        'status' => 'active',
    ])->assertForbidden();

    $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->postJson(route('units.store'), [
        'code' => 'NO-MENU',
        'name' => 'Tidak boleh',
        'status' => 'active',
    ])->assertForbidden();

    $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->postJson(route('cost-centers.store'), [
        'code' => 'NO-MENU',
        'name' => 'Tidak boleh',
        'status' => 'active',
    ])->assertForbidden();

    $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->postJson(route('shifts.store'), [
        'code' => 'NO-MENU',
        'name' => 'Tidak boleh',
        'start_time' => '08:00',
        'end_time' => '17:00',
        'status' => 'active',
    ])->assertForbidden();

    $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->post(route('deductions.store'), [
        'month' => '2026-09',
    ])->assertForbidden();
});

it('blocks product updates and deletes when the products menu is not granted', function () {
    $client = Client::factory()->create();
    $role = guardTestRole('bare-product-mutations', 'Bare Product Mutations Role');
    $user = guardTestUser($client, $role);
    $product = Product::factory()->create(['client_id' => $client->getKey()]);

    $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->putJson(route('products.update', $product), [
        'client_id' => $client->getKey(),
        'sku' => $product->sku,
        'name' => 'Tidak boleh diubah',
        'status' => 'active',
    ])->assertForbidden();

    $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->deleteJson(route('products.destroy', $product))->assertForbidden();
});
