<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function validEmployeePayload(array $overrides = []): array
{
    return array_merge([
        'sim_id' => 'PEG1234',
        'full_name' => 'Ananda Julian',
        'email' => 'ananda@example.com',
        'phone' => '081234567890',
        'join_date' => '2026-01-01',
        'birth_date' => '1995-01-01',
        'gender' => 'male',
        'employee_status' => 'permanent',
        'marital_status' => 'married',
    ], $overrides);
}

it('generates the SIM ID itself and ignores one sent by the form', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('employees.store'), validEmployeePayload());

    $response->assertCreated();
    $employee = Employee::query()->where('client_id', $client->getKey())->firstOrFail();
    expect($employee->employee_no)->toMatch('/^\d{9}$/');
    $this->assertDatabaseHas('employees', [
        'client_id' => $client->getKey(),
        'id' => $employee->getKey(),
        'sim_id' => now()->format('\\I\\DYm').'001',
        'email' => 'ananda@example.com',
        'marital_status' => 'married',
    ]);
    $this->assertDatabaseHas('users', ['id' => $employee->user_id, 'name' => 'Ananda Julian', 'username' => $employee->employee_no, 'email' => 'ananda@example.com', 'status' => 'active']);
    $this->assertDatabaseHas('client_user', ['client_id' => $client->getKey(), 'user_id' => $employee->user_id, 'status' => 'active']);
    expect(Hash::check('01011995', User::query()->findOrFail($employee->user_id)->password))->toBeTrue();
});

it('shows email and a read-only SIM ID but no password in the employee master form', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('employees.index'));

    $response->assertOk()
        ->assertSee('name="email"', false)
        ->assertSee('name="sim_id" readonly', false)
        ->assertDontSee('name="employee_no"', false)
        ->assertDontSee('name="password"', false)
        ->assertSee('Password default: tanggal lahir dengan format ddmmyyyy.', false)
        ->assertSee('name="birth_date"', false)
        ->assertSee('type="radio"', false)
        ->assertSee('name="marital_status" value="single"', false)
        ->assertDontSee('<select name="marital_status"', false)
        ->assertSee('Password dapat diatur dari menu Akses.');
});

it('shows a searchable client selector for super admins', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create(['name' => 'PT Client Pilihan']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('employees.index'));

    $response->assertOk()
        ->assertSee('name="client_id"', false)
        ->assertSee('data-select2-remote="'.route('clients.options').'"', false)
        ->assertDontSee('data-disabled-on-edit', false)
        ->assertSee('data-select2-placeholder=', false)
        ->assertSee('data-employee-client-filter', false)
        ->assertSee('data-select2-placeholder="Semua client"', false)
        ->assertSee('Pilih client', false);
});

it('includes the employee birth date in the edit payload using the datepicker format', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    Employee::factory()->create([
        'client_id' => $client->getKey(),
        'birth_date' => '1995-04-23',
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('employees.index', ['draw' => 1, 'start' => 0, 'length' => 10]));

    $response->assertOk();
    $editPayload = $response->json('data.0.action');

    expect($editPayload)
        ->toContain('&quot;birth_date&quot;:&quot;1995-04-23&quot;')
        ->toContain('&quot;client_id&quot;:'.$client->getKey())
        ->toContain('&quot;client&quot;:');
});

it('shows and filters employees from all clients for super admins', function () {
    $user = User::factory()->superAdmin()->create();
    $currentClient = Client::factory()->create();
    $otherClient = Client::factory()->create();
    Employee::factory()->create(['client_id' => $currentClient->getKey(), 'full_name' => 'Karyawan Client Aktif']);
    Employee::factory()->create(['client_id' => $otherClient->getKey(), 'full_name' => 'Karyawan Client Lain']);

    $allResponse = $this->actingAs($user)
        ->withSession(['current_client_id' => $currentClient->getKey()])
        ->getJson(route('employees.index', ['draw' => 1, 'start' => 0, 'length' => 10]))
        ->assertOk();

    expect(collect($allResponse->json('data'))->pluck('full_name'))
        ->toContain('Karyawan Client Aktif', 'Karyawan Client Lain');

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $currentClient->getKey()])
        ->getJson(route('employees.index', ['draw' => 1, 'start' => 0, 'length' => 10, 'client_id' => $otherClient->getKey()]));

    $names = collect($response->json('data'))->pluck('full_name');

    expect($names)->toContain('Karyawan Client Lain')->not->toContain('Karyawan Client Aktif');
});

it('stores an employee under the selected client', function () {
    $user = User::factory()->superAdmin()->create();
    $currentClient = Client::factory()->create();
    $selectedClient = Client::factory()->create();
    $group = Group::factory()->create(['client_id' => $currentClient->getKey(), 'name' => 'Packing']);
    $selectedGroup = Group::factory()->create(['client_id' => $selectedClient->getKey(), 'name' => 'Selected Packing']);

    $this->actingAs($user)
        ->withSession(['current_client_id' => $currentClient->getKey()])
        ->getJson(route('employees.group-options', ['client_id' => $selectedClient->getKey()]))
        ->assertOk()
        ->assertJsonPath('results.0.id', $selectedGroup->getKey())
        ->assertJsonMissing(['id' => $group->getKey(), 'text' => $group->name]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $currentClient->getKey()])
        ->postJson(route('employees.store'), validEmployeePayload([
            'client_id' => $selectedClient->getKey(),
            'group_id' => $selectedGroup->getKey(),
        ]));

    $response->assertCreated();
    $this->assertDatabaseHas('employees', [
        'client_id' => $selectedClient->getKey(),
        'group_id' => $selectedGroup->getKey(),
        'full_name' => 'Ananda Julian',
    ]);
});

it('requires marital status when storing an employee', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('employees.store'), validEmployeePayload(['marital_status' => '']));

    $response->assertInvalid(['marital_status']);
});

it('creates an employee account that can log in with the generated id', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('employees.store'), validEmployeePayload(['email' => 'login.employee@example.com']))
        ->assertCreated();

    $employee = Employee::query()->where('client_id', $client->getKey())->firstOrFail();
    $this->post(route('logout'));
    $response = $this->post(route('login.store'), [
        'login' => $employee->employee_no,
        'password' => '01011995',
    ]);

    $response->assertRedirectToRoute('dashboard');
    $this->assertAuthenticatedAs(User::query()->findOrFail($employee->user_id));
});

it('allows sim id and email to be left blank', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('employees.store'), validEmployeePayload(['sim_id' => '', 'email' => '']));

    $response->assertCreated();
});

it('updates an employee with sim id, email, and marital status', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey()]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('employees.update', $employee), validEmployeePayload(['marital_status' => 'divorced']));

    $response->assertOk();
    $this->assertDatabaseHas('employees', ['id' => $employee->getKey(), 'employee_no' => $employee->employee_no, 'marital_status' => 'divorced', 'sim_id' => 'PEG1234']);
    $this->assertDatabaseHas('users', ['id' => $employee->fresh()->user_id, 'name' => 'Ananda Julian', 'email' => 'ananda@example.com']);
});

it('lets super admins move an employee to another client and moves account access with it', function () {
    $admin = User::factory()->superAdmin()->create();
    $oldClient = Client::factory()->create();
    $newClient = Client::factory()->create();
    $employeeRole = Role::query()->firstOrCreate(['code' => 'employee'], ['name' => 'Karyawan']);
    $account = User::factory()->create();
    $account->clients()->attach($oldClient, ['role_id' => $employeeRole->getKey(), 'is_default' => true, 'status' => 'active']);
    $oldGroup = Group::factory()->create(['client_id' => $oldClient->getKey()]);
    $newGroup = Group::factory()->create(['client_id' => $newClient->getKey()]);
    $employee = Employee::factory()->create([
        'client_id' => $oldClient->getKey(),
        'user_id' => $account->getKey(),
        'group_id' => $oldGroup->getKey(),
    ]);

    $this->actingAs($admin)
        ->withSession(['current_client_id' => $oldClient->getKey()])
        ->putJson(route('employees.update', $employee), validEmployeePayload([
            'client_id' => $newClient->getKey(),
            'group_id' => $newGroup->getKey(),
        ]))
        ->assertOk();

    $this->assertDatabaseHas('employees', [
        'id' => $employee->getKey(),
        'client_id' => $newClient->getKey(),
        'group_id' => $newGroup->getKey(),
    ]);
    $this->assertDatabaseHas('client_user', [
        'client_id' => $oldClient->getKey(),
        'user_id' => $account->getKey(),
        'status' => 'inactive',
        'is_default' => false,
    ]);
    $this->assertDatabaseHas('client_user', [
        'client_id' => $newClient->getKey(),
        'user_id' => $account->getKey(),
        'status' => 'active',
        'is_default' => true,
    ]);
});

it('rejects a group from a different client when editing an employee', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $otherGroup = Group::factory()->create(['client_id' => $otherClient->getKey()]);
    $employee = Employee::factory()->create(['client_id' => $client->getKey()]);

    $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('employees.update', $employee), validEmployeePayload([
            'client_id' => $client->getKey(),
            'group_id' => $otherGroup->getKey(),
        ]))
        ->assertInvalid(['group_id']);
});

it('preserves leader role when employee group changes', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $leader = User::factory()->create();
    $leaderRole = Role::query()->firstOrCreate(['code' => 'leader'], ['name' => 'Leader']);
    $leader->clients()->attach($client, ['role_id' => $leaderRole->getKey(), 'is_default' => true, 'status' => 'active']);
    $oldGroup = Group::factory()->create(['client_id' => $client->getKey(), 'name' => 'Group Lama']);
    $newGroup = Group::factory()->create(['client_id' => $client->getKey(), 'name' => 'Group Baru']);
    $employee = Employee::factory()->create([
        'client_id' => $client->getKey(),
        'user_id' => $leader->getKey(),
        'group_id' => $oldGroup->getKey(),
    ]);

    $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('employees.update', $employee), validEmployeePayload(['group_id' => $newGroup->getKey()]))
        ->assertOk();

    $this->assertDatabaseHas('client_user', [
        'client_id' => $client->getKey(),
        'user_id' => $leader->getKey(),
        'role_id' => $leaderRole->getKey(),
    ]);

    $this->actingAs($leader)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('dashboard'))
        ->assertViewIs('dashboard-leader')
        ->assertSee('Lingkup data: Group Baru');
});

it('preserves a disabled employee account when employee details are updated', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $account = User::factory()->inactive()->create();
    $role = Role::factory()->create(['code' => 'employee']);
    $account->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'inactive']);
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $account->getKey()]);

    $response = $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('employees.update', $employee), validEmployeePayload(['full_name' => 'Nama Baru']));

    $response->assertOk();
    $this->assertDatabaseHas('users', ['id' => $account->getKey(), 'status' => 'inactive']);
    $this->assertDatabaseHas('client_user', ['client_id' => $client->getKey(), 'user_id' => $account->getKey(), 'status' => 'inactive']);
});

it('keeps an inactive employee group available while editing employees', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $group = Group::factory()->create(['client_id' => $client->getKey(), 'name' => 'Group Nonaktif', 'status' => 'inactive']);
    $otherInactiveGroup = Group::factory()->create(['client_id' => $client->getKey(), 'name' => 'Group Nonaktif Lain', 'status' => 'inactive']);
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'group_id' => $group->getKey()]);

    $response = $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('employees.index'));

    $response->assertOk()->assertSee('Group Nonaktif');

    $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('employees.update', $employee), validEmployeePayload([
            'birth_date' => '1995-04-23',
            'group_id' => $group->getKey(),
        ]))
        ->assertOk();

    $this->assertDatabaseHas('employees', [
        'id' => $employee->getKey(),
        'birth_date' => '1995-04-23',
        'group_id' => $group->getKey(),
    ]);

    $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('employees.update', $employee), validEmployeePayload([
            'group_id' => $otherInactiveGroup->getKey(),
        ]))
        ->assertInvalid(['group_id']);
});

it('includes humanized labels in the detail payload for the datatable', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $group = Group::factory()->create(['client_id' => $client->getKey(), 'name' => 'Group A']);
    Employee::factory()->create([
        'client_id' => $client->getKey(),
        'group_id' => $group->getKey(),
        'gender' => 'female',
        'employee_status' => 'contract',
        'marital_status' => 'widowed',
        'rate_category' => 'lama',
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('employees.index', ['draw' => 1, 'start' => 0, 'length' => 10]));

    $response->assertOk();
    $html = $response->json('data.0.action');

    expect($html)
        ->toContain('data-employee-detail=')
        ->toContain('&quot;gender&quot;:&quot;Perempuan&quot;')
        ->toContain('&quot;employee_status&quot;:&quot;Kontrak&quot;')
        ->toContain('&quot;marital_status&quot;:&quot;Cerai mati&quot;')
        ->toContain('&quot;group_name&quot;:&quot;Group A&quot;');
});

it('previews the next SIM ID for the chosen client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $prefix = now()->format('\\I\\DYm');

    $preview = fn (Client $target) => $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('employees.next-sim-id', ['client_id' => $target->getKey()]));

    $preview($client)->assertOk()->assertJsonPath('sim_id', $prefix.'001');

    Employee::factory()->create(['client_id' => $client->getKey(), 'sim_id' => $prefix.'007']);
    Employee::factory()->create(['client_id' => $client->getKey(), 'sim_id' => 'LAMA-001']);

    $preview($client)->assertOk()->assertJsonPath('sim_id', $prefix.'008');
    $preview($otherClient)->assertOk()->assertJsonPath('sim_id', $prefix.'001');
});

it('refuses a SIM ID preview for a client the user cannot access', function () {
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $user = User::factory()->create();
    $role = Role::query()->firstOrCreate(['code' => 'admin'], ['name' => 'Admin']);
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);

    $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('employees.next-sim-id', ['client_id' => $otherClient->getKey()]))
        ->assertForbidden();
});
