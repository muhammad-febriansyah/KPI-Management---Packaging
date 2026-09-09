<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function grantMenu(Role $role, string $menuKey): void
{
    $permission = Permission::query()->firstOrCreate(['code' => 'menu.'.$menuKey], ['name' => 'Menu: '.$menuKey]);
    $role->permissions()->syncWithoutDetaching([$permission->getKey()]);
}

function attachEmployeeRole(User $user, Client $client): void
{
    $role = Role::query()->firstOrCreate(['code' => 'employee'], ['name' => 'Karyawan']);
    grantMenu($role, 'realizations');
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);
}

function attachClientRole(User $user, Client $client): void
{
    $role = Role::query()->firstOrCreate(['code' => 'client'], ['name' => 'Client']);
    grantMenu($role, 'realizations');
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);
}

function createRealization(Client $client, User $creator, string $workDate): int
{
    $employee = Employee::query()->where('client_id', $client->getKey())->where('user_id', $creator->getKey())->first()
        ?? Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $creator->getKey()]);
    $shift = Shift::factory()->create(['client_id' => $client->getKey()]);
    $unit = Unit::factory()->create(['client_id' => $client->getKey()]);
    $sku = 'SKU-'.$workDate.'-'.$creator->getKey().'-'.uniqid();
    $productId = DB::table('products')->insertGetId([
        'client_id' => $client->getKey(), 'sku' => $sku, 'name' => 'Produk', 'unit_id' => $unit->getKey(),
        'po_price' => 0, 'old_employee_rate' => 0, 'new_employee_rate' => 0, 'status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $realizationId = DB::table('work_realizations')->insertGetId([
        'client_id' => $client->getKey(), 'work_date' => $workDate, 'shift_id' => $shift->getKey(), 'product_id' => $productId,
        'sku_snapshot' => $sku, 'product_name_snapshot' => 'Produk', 'unit_name_snapshot' => 'PCS', 'total_output' => 10,
        'start_time' => '08:00', 'end_time' => '16:00', 'status' => 'assigned', 'created_by' => $creator->getKey(),
        'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('realization_employees')->insert([
        'client_id' => $client->getKey(),
        'work_realization_id' => $realizationId,
        'employee_id' => $employee->getKey(),
        'rate_category_snapshot' => $employee->rate_category,
        'rate_per_unit_snapshot' => 0,
        'allocation_output' => 10,
        'gross_amount' => 0,
        'created_at' => now(),
    ]);

    return $realizationId;
}

it('scopes the realization list to only the employee\'s own entries', function () {
    $client = Client::factory()->create();
    $employeeA = User::factory()->create();
    $employeeB = User::factory()->create();
    attachEmployeeRole($employeeA, $client);
    attachEmployeeRole($employeeB, $client);

    createRealization($client, $employeeA, '2026-08-01');
    createRealization($client, $employeeB, '2026-08-02');

    $response = $this->actingAs($employeeA)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('realizations.index').'?draw=1&start=0&length=10');

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
});

it('lets a super admin see every employee\'s realizations', function () {
    $client = Client::factory()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    $employeeA = User::factory()->create();
    $employeeB = User::factory()->create();
    attachEmployeeRole($employeeA, $client);
    attachEmployeeRole($employeeB, $client);

    createRealization($client, $employeeA, '2026-08-01');
    createRealization($client, $employeeB, '2026-08-02');

    $response = $this->actingAs($superAdmin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('realizations.index').'?draw=1&start=0&length=10');

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
});

it('blocks an employee from viewing another employee\'s realization detail', function () {
    $client = Client::factory()->create();
    $employeeA = User::factory()->create();
    $employeeB = User::factory()->create();
    attachEmployeeRole($employeeA, $client);
    attachEmployeeRole($employeeB, $client);

    $realizationId = createRealization($client, $employeeB, '2026-08-02');

    $response = $this->actingAs($employeeA)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('realizations.show', $realizationId));

    $response->assertNotFound();
});

it('lets an employee view their own realization detail', function () {
    $client = Client::factory()->create();
    $employeeA = User::factory()->create();
    attachEmployeeRole($employeeA, $client);

    $realizationId = createRealization($client, $employeeA, '2026-08-01');

    $response = $this->actingAs($employeeA)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('realizations.show', $realizationId));

    $response->assertOk();
});

it('lets an assigned employee submit the realization result', function () {
    $client = Client::factory()->create();
    $employeeUser = User::factory()->create();
    attachEmployeeRole($employeeUser, $client);
    $realizationId = createRealization($client, $employeeUser, '2026-08-01');

    $response = $this->actingAs($employeeUser)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('realizations.update', $realizationId), [
            'total_output' => 25,
            'start_time' => '08:30',
            'end_time' => '16:30',
            'report' => 'Hasil pekerjaan selesai.',
        ]);

    $response->assertOk();
    $this->assertDatabaseHas('work_realizations', ['id' => $realizationId, 'status' => 'submitted', 'total_output' => 25]);
    $this->assertDatabaseHas('realization_employees', ['work_realization_id' => $realizationId, 'allocation_output' => 25]);
});

it('forbids an employee from submitting another employee\'s assignment', function () {
    $client = Client::factory()->create();
    $employeeA = User::factory()->create();
    $employeeB = User::factory()->create();
    attachEmployeeRole($employeeA, $client);
    attachEmployeeRole($employeeB, $client);
    $realizationId = createRealization($client, $employeeB, '2026-08-02');

    $response = $this->actingAs($employeeA)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('realizations.update', $realizationId), [
            'total_output' => 25,
            'start_time' => '08:30',
            'end_time' => '16:30',
        ]);

    $response->assertForbidden();
});

it('only counts an employee\'s own realizations on their dashboard', function () {
    $client = Client::factory()->create();
    $employeeA = User::factory()->create();
    $employeeB = User::factory()->create();
    attachEmployeeRole($employeeA, $client);
    attachEmployeeRole($employeeB, $client);

    createRealization($client, $employeeA, now()->toDateString());
    createRealization($client, $employeeB, now()->toDateString());
    createRealization($client, $employeeB, now()->subDay()->toDateString());

    $response = $this->actingAs($employeeA)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertViewHas('metrics', fn (array $metrics): bool => $metrics['realizationsTotal'] === 1 && $metrics['realizationsToday'] === 1);
    $response->assertViewHas('recentRealizations', fn ($rows): bool => $rows->count() === 1);
});

it('lets a client role user see every realization for their client, not just their own', function () {
    $client = Client::factory()->create();
    $clientUser = User::factory()->create();
    $employeeA = User::factory()->create();
    $employeeB = User::factory()->create();
    attachClientRole($clientUser, $client);
    attachEmployeeRole($employeeA, $client);
    attachEmployeeRole($employeeB, $client);

    createRealization($client, $employeeA, '2026-08-01');
    createRealization($client, $employeeB, '2026-08-02');

    $response = $this->actingAs($clientUser)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('realizations.index').'?draw=1&start=0&length=10');

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
});

it('lets a client role user view any realization detail for their client', function () {
    $client = Client::factory()->create();
    $clientUser = User::factory()->create();
    $employee = User::factory()->create();
    attachClientRole($clientUser, $client);
    attachEmployeeRole($employee, $client);

    $realizationId = createRealization($client, $employee, '2026-08-01');

    $response = $this->actingAs($clientUser)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('realizations.show', $realizationId));

    $response->assertOk();
});

it('blocks a role without the realizations menu granted from viewing the list', function () {
    $client = Client::factory()->create();
    $user = User::factory()->create();
    $role = Role::query()->firstOrCreate(['code' => 'client'], ['name' => 'Client']);
    // No menu.realizations permission granted to this role.
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('realizations.index'));

    $response->assertForbidden();
})->skip('Menu access enforcement is paused — see User::canAccessMenu().');
