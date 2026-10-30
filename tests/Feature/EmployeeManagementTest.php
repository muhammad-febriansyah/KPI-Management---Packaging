<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

it('generates an employee ID and stores the manually entered SIM ID', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('employees.store'), validEmployeePayload());

    $response->assertCreated();
    $employee = Employee::query()->where('client_id', $client->getKey())->firstOrFail();
    expect($employee->employee_no)->toBe('ID'.now()->format('Ym').'001');
    $this->assertDatabaseHas('employees', [
        'client_id' => $client->getKey(),
        'id' => $employee->getKey(),
        'sim_id' => 'PEG1234',
        'email' => 'ananda@example.com',
        'marital_status' => 'married',
    ]);
    $this->assertDatabaseHas('users', ['id' => $employee->user_id, 'name' => 'Ananda Julian', 'username' => $employee->employee_no, 'email' => 'ananda@example.com', 'status' => 'active']);
    $this->assertDatabaseHas('client_user', ['client_id' => $client->getKey(), 'user_id' => $employee->user_id, 'status' => 'active']);
    expect(Hash::check('01011995', User::query()->findOrFail($employee->user_id)->password))->toBeTrue();
});

it('shows a read-only employee ID and an editable SIM ID in the employee master form', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('employees.index'));

    $response->assertOk()
        ->assertSee('name="email"', false)
        ->assertSee('name="employee_no" readonly', false)
        ->assertSee('name="sim_id" maxlength="100" required', false)
        ->assertSee('name="email" autocomplete="email" required', false)
        ->assertSee('class="grid gap-2 text-sm font-semibold sm:col-span-2"><span>Status karyawan', false)
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

it('requires SIM ID and email when storing an employee', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('employees.store'), validEmployeePayload(['sim_id' => '', 'email' => '']));

    $response->assertInvalid(['sim_id', 'email']);
    $this->assertDatabaseCount('employees', 0);
});

it('requires SIM ID and email when updating an employee', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'sim_id' => 'SIM-LAMA', 'email' => 'lama@example.com']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('employees.update', $employee), validEmployeePayload(['sim_id' => '', 'email' => '']));

    $response->assertInvalid(['sim_id', 'email']);
    $this->assertDatabaseHas('employees', ['id' => $employee->getKey(), 'sim_id' => 'SIM-LAMA', 'email' => 'lama@example.com']);
});

it('increments employee IDs across clients while preserving their manual SIM IDs', function () {
    $admin = User::factory()->superAdmin()->create();
    $firstClient = Client::factory()->create();
    $secondClient = Client::factory()->create();
    $prefix = 'ID'.now()->format('Ym');

    $this->actingAs($admin)->withSession(['current_client_id' => $firstClient->getKey()])
        ->postJson(route('employees.store'), validEmployeePayload(['sim_id' => 'SIM-A']))
        ->assertCreated();
    $this->actingAs($admin)->withSession(['current_client_id' => $firstClient->getKey()])
        ->postJson(route('employees.store'), validEmployeePayload(['client_id' => $secondClient->getKey(), 'sim_id' => 'SIM-B', 'email' => 'second@example.com']))
        ->assertCreated();

    $this->assertDatabaseHas('employees', ['client_id' => $firstClient->getKey(), 'employee_no' => $prefix.'001', 'sim_id' => 'SIM-A']);
    $this->assertDatabaseHas('employees', ['client_id' => $secondClient->getKey(), 'employee_no' => $prefix.'002', 'sim_id' => 'SIM-B']);
    $this->assertDatabaseHas('users', ['username' => $prefix.'002']);
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

it('previews the next globally unique employee ID', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $prefix = now()->format('\\I\\DYm');

    $preview = fn (Client $target) => $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('employees.next-employee-number', ['client_id' => $target->getKey()]));

    $preview($client)->assertOk()->assertJsonPath('employee_no', $prefix.'001');

    Employee::factory()->create(['client_id' => $client->getKey(), 'employee_no' => $prefix.'007']);
    Employee::factory()->create(['client_id' => $client->getKey(), 'sim_id' => $prefix.'099']);

    $preview($client)->assertOk()->assertJsonPath('employee_no', $prefix.'008');
    $preview($otherClient)->assertOk()->assertJsonPath('employee_no', $prefix.'008');
});

it('refuses an employee ID preview for a client the user cannot access', function () {
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $user = User::factory()->create();
    $role = Role::query()->firstOrCreate(['code' => 'admin'], ['name' => 'Admin']);
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);

    $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('employees.next-employee-number', ['client_id' => $otherClient->getKey()]))
        ->assertForbidden();
});

/**
 * Records a realization on the client and assigns the employee to it.
 */
function assignEmployeeToRealization(Employee $employee, Client $client, User $creator, string $sku, string $workDate, float $output, int $gross, ?string $batchNo = null): int
{
    $shift = Shift::factory()->create(['client_id' => $client->getKey(), 'name' => 'Shift '.$sku]);
    $unit = Unit::factory()->create(['client_id' => $client->getKey(), 'name' => 'Box']);
    $productId = DB::table('products')->insertGetId([
        'client_id' => $client->getKey(), 'sku' => $sku, 'name' => 'Produk '.$sku, 'unit_id' => $unit->getKey(),
        'po_price' => 0, 'employee_rate' => 0, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $batchId = $batchNo === null ? null : DB::table('batches')->insertGetId([
        'client_id' => $client->getKey(), 'batch_no' => $batchNo, 'product_id' => $productId, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $realizationId = DB::table('work_realizations')->insertGetId([
        'client_id' => $client->getKey(), 'work_date' => $workDate, 'shift_id' => $shift->getKey(), 'batch_id' => $batchId, 'product_id' => $productId,
        'sku_snapshot' => $sku, 'product_name_snapshot' => 'Produk '.$sku, 'unit_name_snapshot' => 'Box', 'total_output' => $output,
        'start_time' => '08:00', 'end_time' => '16:00', 'created_by' => $creator->getKey(), 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('realization_employees')->insert([
        'client_id' => $client->getKey(), 'work_realization_id' => $realizationId, 'employee_id' => $employee->getKey(),
        'rate_category_snapshot' => 'baru', 'rate_per_unit_snapshot' => 100, 'allocation_output' => $output, 'gross_amount' => $gross, 'created_at' => now(),
    ]);

    return $realizationId;
}

it('shows an amber detail button with the avatar and realizations url in the employee row', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $account = User::factory()->create(['avatar_path' => 'avatars/budi.jpg']);
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $account->getKey(), 'full_name' => 'Budi Uji']);
    Employee::factory()->create(['client_id' => $client->getKey(), 'full_name' => 'Tanpa Akun']);

    $rows = collect($this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('employees.index', ['draw' => 1, 'start' => 0, 'length' => 10]))
        ->json('data'));

    $withAvatar = $rows->firstWhere('full_name', 'Budi Uji')['action'];
    expect($withAvatar)
        ->toContain('bg-amber-100')
        ->not->toContain('bg-slate-100')
        ->toContain(e(json_encode(route('employees.realizations', $employee))))
        ->toContain('avatars\/budi.jpg');
    expect($rows->firstWhere('full_name', 'Tanpa Akun')['action'])->toContain('&quot;avatar_url&quot;:null');
});

it('lists an employee\'s realizations server side with search, paging, and totals', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey()]);
    assignEmployeeToRealization($employee, $client, $admin, 'SKU-AAA', '2026-10-01', 10, 1000, 'B-20261001-0001');
    assignEmployeeToRealization($employee, $client, $admin, 'SKU-BBB', '2026-10-02', 5.5, 550);
    assignEmployeeToRealization($employee, $otherClient, $admin, 'SKU-CCC', '2026-10-03', 4, 400);
    $request = fn (array $query = []) => $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('employees.realizations', $employee).'?'.http_build_query($query + ['draw' => 1, 'start' => 0, 'length' => 10, 'order' => [['column' => 1, 'dir' => 'desc']], 'columns' => collect(['', 'work_date', 'batch_no', 'product', 'shift_name', 'allocation_output', 'gross_amount', 'action'])->map(fn (string $name): array => ['data' => $name, 'searchable' => $name === '' || $name === 'action' ? 'false' : 'true', 'orderable' => $name === '' || $name === 'action' ? 'false' : 'true', 'search' => ['value' => '', 'regex' => 'false']])->all()]));

    $all = $request()->assertOk();
    expect($all->json('recordsTotal'))->toBe(3)
        ->and(collect($all->json('data'))->pluck('sku_snapshot')->all())->toBe(['SKU-CCC', 'SKU-BBB', 'SKU-AAA'])
        ->and($all->json('data.2.work_date'))->toBe('01/10/2026')
        ->and($all->json('summary.total_realizations'))->toBe(3)
        ->and($all->json('summary.total_output'))->toBe(19.5)
        ->and($all->json('summary.total_gross'))->toBe(1950);
    expect($all->json('data.0.action'))->toContain('client_id='.$otherClient->getKey());

    $request(['search' => ['value' => 'B-20261001']])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.sku_snapshot', 'SKU-AAA');
    $request(['search' => ['value' => '02/10/2026']])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.sku_snapshot', 'SKU-BBB');
    $request(['search' => ['value' => 'SKU-']])->assertJsonPath('recordsFiltered', 3);
    $paged = $request(['start' => 2, 'length' => 2]);
    expect($paged->json('data'))->toHaveCount(1)
        ->and($paged->json('recordsFiltered'))->toBe(3);
});

it('limits a client admin to realizations owned by the active client', function () {
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $creator = User::factory()->superAdmin()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey()]);
    $otherEmployee = Employee::factory()->create(['client_id' => $otherClient->getKey()]);
    assignEmployeeToRealization($employee, $client, $creator, 'SKU-OWN', '2026-10-01', 10, 1000);
    assignEmployeeToRealization($employee, $otherClient, $creator, 'SKU-FOREIGN', '2026-10-02', 4, 400);
    $admin = User::factory()->create();
    $role = Role::query()->firstOrCreate(['code' => 'admin'], ['name' => 'Admin']);
    $role->permissions()->syncWithoutDetaching([Permission::query()->firstOrCreate(['code' => 'menu.employees'], ['name' => 'Menu: Karyawan'])->getKey()]);
    $admin->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);

    $response = $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('employees.realizations', $employee).'?draw=1&start=0&length=10')
        ->assertOk();

    expect(collect($response->json('data'))->pluck('sku_snapshot')->all())->toBe(['SKU-OWN'])
        ->and($response->json('summary.total_realizations'))->toBe(1)
        ->and($response->json('summary.total_gross'))->toBe(1000);

    $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('employees.realizations', $otherEmployee))
        ->assertNotFound();
});

it('forbids the employee realizations list without the employees menu', function () {
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey()]);
    $user = User::factory()->create();
    $role = Role::query()->firstOrCreate(['code' => 'employee'], ['name' => 'Karyawan']);
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);

    $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('employees.realizations', $employee))
        ->assertForbidden();
});
