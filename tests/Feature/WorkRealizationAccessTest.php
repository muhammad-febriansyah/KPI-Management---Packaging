<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use App\Models\WorkRealization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        'po_price' => 0, 'employee_rate' => 0, 'status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $realizationId = DB::table('work_realizations')->insertGetId([
        'client_id' => $client->getKey(), 'work_date' => $workDate, 'shift_id' => $shift->getKey(), 'product_id' => $productId,
        'sku_snapshot' => $sku, 'product_name_snapshot' => 'Produk', 'unit_name_snapshot' => 'PCS', 'total_output' => 10,
        'start_time' => '08:00', 'end_time' => '16:00', 'created_by' => $creator->getKey(),
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
    expect($response->json('data.0.action'))
        ->not->toContain('data-realization-fill-open')
        ->not->toContain('Edit hasil');
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
    DB::table('realization_employees')
        ->where('work_realization_id', $realizationId)
        ->update(['rate_per_unit_snapshot' => 25]);

    $response = $this->actingAs($employeeA)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('realizations.show', $realizationId));

    $response->assertOk()
        ->assertDontSee('data-realization-submit-form', false)
        ->assertDontSee('Isi hasil pekerjaan')
        ->assertSee('Data realisasi pekerjaan')
        ->assertSee('Tanggal borongan')
        ->assertSee('Nomor batch')
        ->assertSee('SKU')
        ->assertSee('Nama produk')
        ->assertSee('Total (Karton/Kg)')
        ->assertSee('Total harga')
        ->assertSee('Rp 250')
        ->assertSee('Waktu pengerjaan (1)')
        ->assertSee('Waktu pengerjaan (2)')
        ->assertSee('Report')
        ->assertSee('data-realization-image-placeholder', false)
        ->assertSee('No image')
        ->assertSee('heroicons.svg#photo', false);
});

it('hides the assignment controls on the realization detail page', function () {
    $client = Client::factory()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    $employee = User::factory()->create();
    attachEmployeeRole($employee, $client);
    $realizationId = createRealization($client, $employee, '2026-08-01');

    $response = $this->actingAs($superAdmin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('realizations.show', $realizationId));

    $response->assertOk()
        ->assertDontSee('Assign ke karyawan')
        ->assertDontSee('data-realization-assign-form', false)
        ->assertSee('Karyawan yang ditugaskan');
});

it('lets an assigned employee update the realization result', function () {
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
    $this->assertDatabaseHas('work_realizations', ['id' => $realizationId, 'total_output' => 25]);
    $this->assertDatabaseHas('realization_employees', ['work_realization_id' => $realizationId, 'allocation_output' => 25]);
});

it('stores an employee result image', function () {
    Storage::fake('public');
    $client = Client::factory()->create();
    $employeeUser = User::factory()->create();
    attachEmployeeRole($employeeUser, $client);
    $realizationId = createRealization($client, $employeeUser, '2026-08-01');

    $response = $this->actingAs($employeeUser)
        ->withSession(['current_client_id' => $client->getKey()])
        ->put(route('realizations.update', $realizationId), [
            'total_output' => 25,
            'result_image' => UploadedFile::fake()->image('hasil.jpg')->size(1024),
        ]);

    $response->assertOk();
    $path = WorkRealization::query()->findOrFail($realizationId)->result_image_path;
    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);

    $detailResponse = $this->actingAs($employeeUser)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('realizations.show', $realizationId));

    $detailResponse->assertOk()
        ->assertSee('Foto hasil pekerjaan')
        ->assertDontSee('data-realization-image-placeholder', false);
});

it('rejects an employee result image larger than 3 MB', function () {
    Storage::fake('public');
    $client = Client::factory()->create();
    $employeeUser = User::factory()->create();
    attachEmployeeRole($employeeUser, $client);
    $realizationId = createRealization($client, $employeeUser, '2026-08-01');

    $response = $this->actingAs($employeeUser)
        ->withSession(['current_client_id' => $client->getKey()])
        ->put(route('realizations.update', $realizationId), [
            'result_image' => UploadedFile::fake()->image('hasil.jpg')->size(3073),
        ]);

    $response->assertSessionHasErrors('result_image');
    expect(WorkRealization::query()->findOrFail($realizationId)->result_image_path)->toBeNull();
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

it('counts realizations created by an employee even when assigned to another employee', function () {
    $client = Client::factory()->create();
    $employeeUser = User::factory()->create();
    $otherUser = User::factory()->create();
    attachEmployeeRole($employeeUser, $client);
    attachEmployeeRole($otherUser, $client);
    $otherEmployee = Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $otherUser->getKey()]);

    WorkRealization::query()->create([
        'client_id' => $client->getKey(),
        'work_date' => now()->toDateString(),
        'total_output' => 12,
        'created_by' => $employeeUser->getKey(),
    ])->employeeAssignments()->create([
        'client_id' => $client->getKey(),
        'employee_id' => $otherEmployee->getKey(),
        'rate_category_snapshot' => $otherEmployee->rate_category,
        'rate_per_unit_snapshot' => 0,
        'allocation_output' => 12,
        'gross_amount' => 0,
    ]);

    $response = $this->actingAs($employeeUser)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertViewHas('metrics', fn (array $metrics): bool => $metrics['realizationsTotal'] === 1 && $metrics['realizationsToday'] === 1);
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
});
