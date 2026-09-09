<?php

use App\Models\Batch;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use App\Models\WorkRealization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('shows the realization create page instead of a modal', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('realizations.create'));

    $response->assertOk();
    $response->assertViewIs('realizations.create');
    $response->assertViewHas(['shifts', 'batches', 'products', 'employees']);
    $response->assertSee('Tambah Realisasi');
});

it('forbids an employee from opening the assignment form', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();
    $role = Role::query()->firstOrCreate(['code' => 'employee'], ['name' => 'Karyawan']);
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('realizations.create'));

    $response->assertForbidden();
});

it('lets a superadmin create an assignment for a linked employee', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $employeeUser = User::factory()->create();
    $client = Client::factory()->create();
    $role = Role::query()->firstOrCreate(['code' => 'employee'], ['name' => 'Karyawan']);
    $employeeUser->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $employeeUser->getKey()]);
    $shift = Shift::factory()->create(['client_id' => $client->getKey()]);
    $unit = Unit::factory()->create(['client_id' => $client->getKey()]);
    $productId = DB::table('products')->insertGetId([
        'client_id' => $client->getKey(), 'sku' => 'SKU-ASSIGN-001', 'name' => 'Produk Assignment', 'unit_id' => $unit->getKey(),
        'po_price' => 0, 'old_employee_rate' => 10, 'new_employee_rate' => 12, 'status' => 'active',
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
    $this->assertDatabaseHas('work_realizations', ['id' => $realizationId, 'status' => 'assigned']);
    $this->assertDatabaseHas('realization_employees', ['work_realization_id' => $realizationId, 'employee_id' => $employee->getKey()]);

    $tableResponse = $this->actingAs($superAdmin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('realizations.index', ['draw' => 1, 'start' => 0, 'length' => 10]));

    $tableResponse->assertOk()->assertJsonPath('data.0.total_price', 'Rp 120');
});

it('lets a superadmin save a realization without any form values or assignment', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($superAdmin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), []);

    $response->assertCreated();
    $realization = WorkRealization::query()->where('client_id', $client->getKey())->firstOrFail();
    $this->assertDatabaseHas('work_realizations', ['id' => $realization->getKey(), 'status' => 'draft']);
    expect($realization->employeeAssignments()->count())->toBe(0);
});

it('lets a superadmin assign an employee after saving a realization', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $employeeUser = User::factory()->create();
    $client = Client::factory()->create();
    $role = Role::query()->firstOrCreate(['code' => 'employee'], ['name' => 'Karyawan']);
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
    $this->assertDatabaseHas('work_realizations', ['id' => $realizationId, 'status' => 'assigned']);
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

it('filters batch options to only those belonging to the selected product', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $unit = Unit::factory()->create(['client_id' => $client->getKey()]);
    $productA = Product::query()->create(['client_id' => $client->getKey(), 'sku' => 'SKU-A', 'name' => 'Produk A', 'unit_id' => $unit->getKey(), 'po_price' => 0, 'old_employee_rate' => 0, 'new_employee_rate' => 0, 'status' => 'active']);
    $productB = Product::query()->create(['client_id' => $client->getKey(), 'sku' => 'SKU-B', 'name' => 'Produk B', 'unit_id' => $unit->getKey(), 'po_price' => 0, 'old_employee_rate' => 0, 'new_employee_rate' => 0, 'status' => 'active']);
    $batchA = Batch::query()->create(['client_id' => $client->getKey(), 'batch_no' => 'BATCH-A', 'product_id' => $productA->getKey(), 'status' => 'active']);
    Batch::query()->create(['client_id' => $client->getKey(), 'batch_no' => 'BATCH-B', 'product_id' => $productB->getKey(), 'status' => 'active']);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('batches.options', ['product_id' => $productA->getKey()]));

    $response->assertOk()->assertExactJson(['results' => [['id' => $batchA->getKey(), 'text' => 'BATCH-A']]]);
});

it('returns every active batch when no product is selected', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $unit = Unit::factory()->create(['client_id' => $client->getKey()]);
    $product = Product::query()->create(['client_id' => $client->getKey(), 'sku' => 'SKU-A', 'name' => 'Produk A', 'unit_id' => $unit->getKey(), 'po_price' => 0, 'old_employee_rate' => 0, 'new_employee_rate' => 0, 'status' => 'active']);
    Batch::query()->create(['client_id' => $client->getKey(), 'batch_no' => 'BATCH-A', 'product_id' => $product->getKey(), 'status' => 'active']);
    Batch::query()->create(['client_id' => $client->getKey(), 'batch_no' => 'BATCH-NONE', 'product_id' => null, 'status' => 'active']);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('batches.options'));

    $response->assertOk()->assertJsonCount(2, 'results');
});
