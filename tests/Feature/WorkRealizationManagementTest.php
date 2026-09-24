<?php

use App\Models\Batch;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Product;
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

function realizationEmployeeRole(): Role
{
    $role = Role::query()->firstOrCreate(['code' => 'employee'], ['name' => 'Karyawan']);
    $permission = Permission::query()->firstOrCreate(['code' => 'menu.realizations'], ['name' => 'Menu: Realisasi']);
    $role->permissions()->syncWithoutDetaching([$permission->getKey()]);

    return $role;
}

it('shows the realization create page instead of a modal', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('realizations.create'));

    $response->assertOk();
    $response->assertViewIs('realizations.create');
    $response->assertViewHas('employees');
    $response->assertSee('Tambah Realisasi');
    $response->assertSee('data-tom-select-placeholder="Cari group..."', false);
    $response->assertSee('w-80 max-w-full', false);
    $response->assertSee('name="total_output"', false);
    $response->assertSee('data-total-price-preview', false);
    $response->assertSee('data-total-price-preview readonly', false);
    $response->assertDontSee('data-current-rate-category', false);
    $response->assertDontSee('data-employee-rate-category', false);
    $response->assertSee('name="start_time"', false);
    $response->assertSee('name="end_time"', false);
    $response->assertSee('name="report"', false);
    $response->assertSee('name="result_image"', false);
    $response->assertSee('Maksimal 3 MB', false);
    $response->assertSee('data-file-max-size="3145728"', false);
    $response->assertSee('data-file-compress="true"', false);
    $response->assertSee('data-file-preview class="hidden size-16 rounded-lg border border-line bg-slate-800 object-contain p-1"', false);
    $response->assertSee('data-batch-select data-tom-select data-tom-select-remote=', false);
    $response->assertSee('data-tom-select-depends-on="product_id"', false);
});

it('lets an employee open the realization form with assignment controls', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();
    $role = realizationEmployeeRole();
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('realizations.create'));

    $response->assertOk()
        ->assertViewIs('realizations.create')
        ->assertSee('Assign karyawan (opsional)')
        ->assertSee('data-assignment-section', false)
        ->assertSee('data-assignment-group', false)
        ->assertSee('data-assignment-employee', false);
});

it('automatically assigns an employee-created realization to its creator', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();
    $role = realizationEmployeeRole();
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $user->getKey()]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), []);

    $response->assertCreated();
    $realization = WorkRealization::query()->where('created_by', $user->getKey())->firstOrFail();
    $this->assertDatabaseHas('work_realizations', ['id' => $realization->getKey()]);
    $this->assertDatabaseHas('realization_employees', ['work_realization_id' => $realization->getKey(), 'employee_id' => $employee->getKey()]);
});

it('lets an employee assign a realization to selected employees', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $client = Client::factory()->create();
    $role = realizationEmployeeRole();
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);
    $otherUser->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $user->getKey()]);
    $otherEmployee = Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $otherUser->getKey()]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), ['employee_ids' => [$employee->getKey(), $otherEmployee->getKey()]]);

    $response->assertCreated();
    $realization = WorkRealization::query()->where('created_by', $user->getKey())->firstOrFail();
    $this->assertDatabaseHas('realization_employees', ['work_realization_id' => $realization->getKey(), 'employee_id' => $employee->getKey()]);
    $this->assertDatabaseHas('realization_employees', ['work_realization_id' => $realization->getKey(), 'employee_id' => $otherEmployee->getKey()]);
});

it('lets the employee who created a realization assign another employee later', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $client = Client::factory()->create();
    $role = realizationEmployeeRole();
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);
    $otherUser->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);
    Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $user->getKey()]);
    $otherEmployee = Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $otherUser->getKey()]);

    $createResponse = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), []);
    $realization = WorkRealization::query()->where('created_by', $user->getKey())->firstOrFail();

    $assignResponse = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.assign', $realization), ['employee_ids' => [$otherEmployee->getKey()]]);

    $createResponse->assertCreated();
    $assignResponse->assertOk();
    $this->assertDatabaseHas('realization_employees', ['work_realization_id' => $realization->getKey(), 'employee_id' => $otherEmployee->getKey()]);
});

it('forbids an employee from assigning another employee\'s realization', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $client = Client::factory()->create();
    $role = realizationEmployeeRole();
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);
    $otherUser->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);
    Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $user->getKey()]);
    $otherEmployee = Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $otherUser->getKey()]);

    $realization = WorkRealization::query()->create([
        'client_id' => $client->getKey(),
        'created_by' => $otherUser->getKey(),
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.assign', $realization), ['employee_ids' => [$otherEmployee->getKey()]]);

    $response->assertForbidden();
});

it('lets a superadmin create an assignment for a linked employee', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $employeeUser = User::factory()->create();
    $client = Client::factory()->create();
    $role = realizationEmployeeRole();
    $employeeUser->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $employeeUser->getKey()]);
    $shift = Shift::factory()->create(['client_id' => $client->getKey()]);
    $unit = Unit::factory()->create(['client_id' => $client->getKey()]);
    $productId = DB::table('products')->insertGetId([
        'client_id' => $client->getKey(), 'sku' => 'SKU-ASSIGN-001', 'name' => 'Produk Assignment', 'unit_id' => $unit->getKey(),
        'po_price' => 0, 'employee_rate' => 12, 'status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $batchId = DB::table('batches')->insertGetId([
        'client_id' => $client->getKey(), 'batch_no' => 'BATCH-ASSIGN-001', 'product_id' => $productId, 'status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $response = $this->actingAs($superAdmin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), [
            'work_date' => '2026-09-09',
            'shift_id' => $shift->getKey(),
            'batch_id' => $batchId,
            'product_id' => $productId,
            'total_output' => 10,
            'employee_ids' => [$employee->getKey()],
        ]);

    $response->assertCreated();
    $realizationId = DB::table('work_realizations')->where('created_by', $superAdmin->getKey())->value('id');
    expect($realizationId)->not->toBeNull();
    $this->assertDatabaseHas('work_realizations', ['id' => $realizationId]);
    $this->assertDatabaseHas('realization_employees', ['work_realization_id' => $realizationId, 'employee_id' => $employee->getKey()]);

    $tableResponse = $this->actingAs($superAdmin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('realizations.index', ['draw' => 1, 'start' => 0, 'length' => 10]));

    $tableResponse->assertOk()
        ->assertJsonPath('data.0.sku_snapshot', 'SKU-ASSIGN-001')
        ->assertJsonPath('data.0.total_output', '10')
        ->assertJsonPath('data.0.total_price', 'Rp 120');
});

it('uses one product rate for employees from every rate category', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $newEmployeeUser = User::factory()->create();
    $oldEmployeeUser = User::factory()->create();
    $client = Client::factory()->create();
    $role = realizationEmployeeRole();
    $newEmployeeUser->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);
    $oldEmployeeUser->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);
    $newEmployee = Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $newEmployeeUser->getKey(), 'rate_category' => 'baru']);
    $oldEmployee = Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $oldEmployeeUser->getKey(), 'rate_category' => 'lama']);
    $shift = Shift::factory()->create(['client_id' => $client->getKey()]);
    $unit = Unit::factory()->create(['client_id' => $client->getKey()]);
    $product = Product::factory()->create(['client_id' => $client->getKey(), 'unit_id' => $unit->getKey(), 'employee_rate' => 12]);
    $batch = Batch::query()->create(['client_id' => $client->getKey(), 'batch_no' => 'BATCH-UNIFIED-RATE', 'product_id' => $product->getKey(), 'status' => 'active']);

    $response = $this->actingAs($superAdmin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), [
            'work_date' => '2026-09-09',
            'shift_id' => $shift->getKey(),
            'batch_id' => $batch->getKey(),
            'product_id' => $product->getKey(),
            'total_output' => 10,
            'employee_ids' => [$newEmployee->getKey(), $oldEmployee->getKey()],
        ]);

    $response->assertCreated();
    $realizationId = WorkRealization::query()->where('created_by', $superAdmin->getKey())->value('id');

    $this->assertDatabaseHas('realization_employees', [
        'work_realization_id' => $realizationId,
        'employee_id' => $newEmployee->getKey(),
        'rate_category_snapshot' => 'baru',
        'rate_per_unit_snapshot' => 12,
        'gross_amount' => 120,
    ]);
    $this->assertDatabaseHas('realization_employees', [
        'work_realization_id' => $realizationId,
        'employee_id' => $oldEmployee->getKey(),
        'rate_category_snapshot' => 'lama',
        'rate_per_unit_snapshot' => 12,
        'gross_amount' => 120,
    ]);
});

it('stores result fields entered on the realization form', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($superAdmin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), [
            'total_output' => 42,
            'start_time' => '08:00',
            'end_time' => '16:30',
            'report' => 'Hasil pekerjaan tercatat dari form realisasi.',
        ]);

    $response->assertCreated();
    $realization = WorkRealization::query()->where('client_id', $client->getKey())->firstOrFail();
    expect((float) $realization->total_output)->toBe(42.0)
        ->and($realization->start_time)->toStartWith('08:00')
        ->and($realization->end_time)->toStartWith('16:30')
        ->and($realization->report)->toBe('Hasil pekerjaan tercatat dari form realisasi.');
});

it('stores a result image uploaded while creating a realization', function () {
    Storage::fake('public');
    $superAdmin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($superAdmin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->post(route('realizations.store'), [
            'result_image' => UploadedFile::fake()->image('hasil.jpg')->size(1024),
        ]);

    $response->assertCreated();
    $realization = WorkRealization::query()->where('client_id', $client->getKey())->firstOrFail();
    expect($realization->result_image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($realization->result_image_path);
});

it('rejects a result image larger than 3 MB while creating a realization', function () {
    Storage::fake('public');
    $superAdmin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($superAdmin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->post(route('realizations.store'), [
            'result_image' => UploadedFile::fake()->image('hasil.jpg')->size(3073),
        ]);

    $response->assertSessionHasErrors('result_image');
    expect(WorkRealization::query()->where('client_id', $client->getKey())->count())->toBe(0);
});

it('rejects a non-image result file while creating a realization', function () {
    Storage::fake('public');
    $superAdmin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($superAdmin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->post(route('realizations.store'), [
            'result_image' => UploadedFile::fake()->create('hasil.pdf', 100, 'application/pdf'),
        ]);

    $response->assertSessionHasErrors('result_image');
    expect(WorkRealization::query()->where('client_id', $client->getKey())->count())->toBe(0);
});

it('lets a superadmin save a realization without any form values or assignment', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($superAdmin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), []);

    $response->assertCreated();
    $realization = WorkRealization::query()->where('client_id', $client->getKey())->firstOrFail();
    $this->assertDatabaseHas('work_realizations', ['id' => $realization->getKey()]);
    expect($realization->employeeAssignments()->count())->toBe(0);
});

it('lets a superadmin assign an employee after saving a realization', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $employeeUser = User::factory()->create();
    $client = Client::factory()->create();
    $role = realizationEmployeeRole();
    $employeeUser->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $employeeUser->getKey()]);

    $realizationResponse = $this->actingAs($superAdmin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), []);
    $realizationId = WorkRealization::query()->where('client_id', $client->getKey())->value('id');

    $response = $this->actingAs($superAdmin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.assign', $realizationId), ['employee_ids' => [$employee->getKey()]]);

    $realizationResponse->assertCreated();
    $response->assertOk();
    $this->assertDatabaseHas('realization_employees', ['work_realization_id' => $realizationId, 'employee_id' => $employee->getKey()]);
    $this->assertDatabaseHas('work_realizations', ['id' => $realizationId]);
});

it('renders the realization assignment modal on the index page', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('realizations.index'));

    $response->assertOk();
    $response->assertSee('data-realization-assign-modal', false);
    $response->assertSee('data-realization-assign-employee', false);
    $response->assertSee('max-w-6xl', false);
    $response->assertSee('min-h-[60vh]', false);
    $response->assertDontSee('<th>Assign</th>', false);
    $response->assertDontSee('<th>Status</th>', false);
    $response->assertSee('<th>Total harga</th>', false);
    $response->assertSee(route('realizations.create'), false);
});

it('places the realization assignment button inside the action column', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $storeResponse = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), []);
    $storeResponse->assertCreated();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('realizations.index', ['draw' => 1, 'start' => 0, 'length' => 10]));

    $response->assertOk();
    $action = $response->json('data.0.action');
    expect($action)->toContain('data-realization-assign-open')->toContain('bg-orange-50')->toContain('heroicons.svg#users')->toContain('Detail')->toContain('Assign');
    expect($response->json('data.0.assignment_action'))->toBeNull();
});

it('passes existing employee assignments to the assignment modal', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $employeeUser = User::factory()->create();
    $client = Client::factory()->create();
    $role = Role::query()->firstOrCreate(['code' => 'employee'], ['name' => 'Karyawan']);
    $employeeUser->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $employeeUser->getKey()]);

    $storeResponse = $this->actingAs($superAdmin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), ['employee_ids' => [$employee->getKey()]]);
    $storeResponse->assertCreated();

    $response = $this->actingAs($superAdmin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('realizations.index', ['draw' => 1, 'start' => 0, 'length' => 10]));

    $response->assertOk();
    expect($response->json('data.0.action'))->toContain('data-assigned-employee-ids="['.$employee->getKey().']"');
});

it('filters batch options to only those belonging to the selected product', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $unit = Unit::factory()->create(['client_id' => $client->getKey()]);
    $productA = Product::query()->create(['client_id' => $client->getKey(), 'sku' => 'SKU-A', 'name' => 'Produk A', 'unit_id' => $unit->getKey(), 'po_price' => 0, 'employee_rate' => 0, 'status' => 'active']);
    $productB = Product::query()->create(['client_id' => $client->getKey(), 'sku' => 'SKU-B', 'name' => 'Produk B', 'unit_id' => $unit->getKey(), 'po_price' => 0, 'employee_rate' => 0, 'status' => 'active']);
    $batchA = Batch::query()->create(['client_id' => $client->getKey(), 'batch_no' => 'BATCH-A', 'product_id' => $productA->getKey(), 'status' => 'active']);
    Batch::query()->create(['client_id' => $client->getKey(), 'batch_no' => 'BATCH-B', 'product_id' => $productB->getKey(), 'status' => 'active']);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('batches.options', ['product_id' => $productA->getKey()]));

    $response->assertOk()->assertExactJson(['results' => [[
        'id' => $batchA->getKey(),
        'text' => 'BATCH-A',
        'product_id' => $productA->getKey(),
        'product' => [
            'id' => $productA->getKey(),
            'text' => 'SKU-A — Produk A',
            'name' => 'Produk A',
            'unit_name' => $unit->name,
            'employee_rate' => '0.000',
            'estimated_output_per_hour' => null,
        ],
    ]]]);
});

it('returns every active batch when no product is selected', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $unit = Unit::factory()->create(['client_id' => $client->getKey()]);
    $product = Product::query()->create(['client_id' => $client->getKey(), 'sku' => 'SKU-A', 'name' => 'Produk A', 'unit_id' => $unit->getKey(), 'po_price' => 0, 'employee_rate' => 0, 'status' => 'active']);
    Batch::query()->create(['client_id' => $client->getKey(), 'batch_no' => 'BATCH-A', 'product_id' => $product->getKey(), 'status' => 'active']);
    Batch::query()->create(['client_id' => $client->getKey(), 'batch_no' => 'BATCH-NONE', 'product_id' => null, 'status' => 'active']);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('batches.options'));

    $response->assertOk()->assertJsonCount(2, 'results');
});
