<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makeAssignedRealization(Client $client, User $admin, Employee $employee): int
{
    $shift = Shift::factory()->create(['client_id' => $client->getKey()]);
    $unit = Unit::factory()->create(['client_id' => $client->getKey()]);
    $product = Product::query()->create([
        'client_id' => $client->getKey(), 'sku' => 'SKU-WR-'.uniqid(), 'name' => 'Produk', 'unit_id' => $unit->getKey(),
        'po_price' => 0, 'employee_rate' => 0, 'status' => 'active',
    ]);

    $realizationId = DB::table('work_realizations')->insertGetId([
        'client_id' => $client->getKey(), 'work_date' => now()->toDateString(), 'shift_id' => $shift->getKey(), 'product_id' => $product->getKey(),
        'sku_snapshot' => $product->sku, 'product_name_snapshot' => $product->name, 'unit_name_snapshot' => 'PCS',
        'total_output' => 100, 'start_time' => '08:00', 'end_time' => '16:00', 'created_by' => $admin->getKey(),
        'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('realization_employees')->insert([
        'client_id' => $client->getKey(), 'work_realization_id' => $realizationId, 'employee_id' => $employee->getKey(),
        'rate_category_snapshot' => $employee->rate_category, 'rate_per_unit_snapshot' => 0, 'allocation_output' => null,
        'gross_amount' => 0, 'created_at' => now(),
    ]);

    return $realizationId;
}

it('lists assigned realizations without exposing a status field', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey()]);
    makeAssignedRealization($client, $admin, $employee);
    makeAssignedRealization($client, $admin, $employee);

    $response = $this->actingAs($admin)->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('reports.work').'?draw=1&start=0&length=10');

    $response->assertOk();
    $response->assertJsonCount(2, 'data')->assertJsonMissingPath('data.0.status');
});

it('reads the report row fields from the assigned realization', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create([
        'client_id' => $client->getKey(),
        'sim_id' => 'SIM-REAL-001',
        'full_name' => 'Karyawan Realisasi',
    ]);
    $realizationId = makeAssignedRealization($client, $admin, $employee);

    DB::table('work_realizations')->where('id', $realizationId)->update([
        'work_date' => '2026-09-08',
        'product_name_snapshot' => 'Produk dari Realisasi',
        'total_output' => 380,
        'report' => 'Catatan dari realisasi',
    ]);
    DB::table('products')->where('id', DB::table('work_realizations')->where('id', $realizationId)->value('product_id'))->update([
        'estimated_output_per_hour' => 50,
        'po_price' => 4000,
    ]);

    $response = $this->actingAs($admin)->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('reports.work').'?draw=1&start=0&length=10');

    $response->assertOk()
        ->assertJsonPath('data.0.work_date', '08/09/2026')
        ->assertJsonPath('data.0.sim_id', 'SIM-REAL-001')
        ->assertJsonPath('data.0.full_name', 'Karyawan Realisasi')
        ->assertJsonPath('data.0.product_name', 'Produk dari Realisasi')
        ->assertJsonPath('data.0.target', '400')
        ->assertJsonPath('data.0.target_price', 'Rp 1.600.000')
        ->assertJsonPath('data.0.actual', '380')
        ->assertJsonPath('data.0.actual_price', 'Rp 1.520.000')
        ->assertJsonPath('data.0.description', 'Catatan dari realisasi');
});

it('does not render a status filter on the work report page', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('reports.work'));

    $response->assertOk()
        ->assertDontSee('data-work-status', false)
        ->assertDontSee('Semua status');
});

it('only lists realizations assigned to an employee, not unassigned ones', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey()]);
    makeAssignedRealization($client, $admin, $employee);

    // An unassigned realization (no row in realization_employees) must not appear.
    DB::table('work_realizations')->insert([
        'client_id' => $client->getKey(), 'work_date' => now()->toDateString(), 'created_by' => $admin->getKey(),
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $response = $this->actingAs($admin)->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('reports.work').'?draw=1&start=0&length=10');

    $response->assertOk()->assertJsonCount(1, 'data');
});

it('ignores a legacy status filter parameter', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey()]);
    makeAssignedRealization($client, $admin, $employee);
    makeAssignedRealization($client, $admin, $employee);

    $response = $this->actingAs($admin)->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('reports.work').'?draw=1&start=0&length=10&status=assigned');

    $response->assertOk()->assertJsonCount(2, 'data')->assertJsonMissingPath('data.0.status');
});

it('ignores an undefined legacy status filter parameter', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey()]);
    makeAssignedRealization($client, $admin, $employee);

    $response = $this->actingAs($admin)->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('reports.work').'?draw=1&start=0&length=10&status=undefined');

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.status');
});

it('only reports products and work results from the active client', function () {
    $admin = User::factory()->superAdmin()->create();
    $activeClient = Client::factory()->create(['name' => 'PT Client Aktif']);
    $otherClient = Client::factory()->create(['name' => 'PT Client Lain']);
    $activeEmployee = Employee::factory()->create(['client_id' => $activeClient->getKey()]);
    $otherEmployee = Employee::factory()->create(['client_id' => $otherClient->getKey()]);

    makeAssignedRealization($activeClient, $admin, $activeEmployee);
    makeAssignedRealization($otherClient, $admin, $otherEmployee);

    $response = $this->actingAs($admin)
        ->withSession(['current_client_id' => $activeClient->getKey()])
        ->getJson(route('reports.work').'?draw=1&start=0&length=10');

    $response->assertOk()->assertJsonCount(1, 'data');
    expect($response->json('data.0.full_name'))->toBe($activeEmployee->full_name);
});
