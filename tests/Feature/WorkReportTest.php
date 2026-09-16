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

function makeAssignedRealization(Client $client, User $admin, Employee $employee, string $status): int
{
    $shift = Shift::factory()->create(['client_id' => $client->getKey()]);
    $unit = Unit::factory()->create(['client_id' => $client->getKey()]);
    $product = Product::query()->create([
        'client_id' => $client->getKey(), 'sku' => 'SKU-WR-'.uniqid(), 'name' => 'Produk', 'unit_id' => $unit->getKey(),
        'po_price' => 0, 'old_employee_rate' => 0, 'new_employee_rate' => 0, 'status' => 'active',
    ]);

    $realizationId = DB::table('work_realizations')->insertGetId([
        'client_id' => $client->getKey(), 'work_date' => now()->toDateString(), 'shift_id' => $shift->getKey(), 'product_id' => $product->getKey(),
        'sku_snapshot' => $product->sku, 'product_name_snapshot' => $product->name, 'unit_name_snapshot' => 'PCS',
        'total_output' => 100, 'start_time' => '08:00', 'end_time' => '16:00', 'status' => $status, 'created_by' => $admin->getKey(),
        'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('realization_employees')->insert([
        'client_id' => $client->getKey(), 'work_realization_id' => $realizationId, 'employee_id' => $employee->getKey(),
        'rate_category_snapshot' => $employee->rate_category, 'rate_per_unit_snapshot' => 0, 'allocation_output' => null,
        'gross_amount' => 0, 'created_at' => now(),
    ]);

    return $realizationId;
}

it('marks a pending realization as belum dikerjakan and a submitted one as sudah dikerjakan', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey()]);
    makeAssignedRealization($client, $admin, $employee, 'assigned');
    makeAssignedRealization($client, $admin, $employee, 'submitted');

    $response = $this->actingAs($admin)->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('reports.work').'?draw=1&start=0&length=10');

    $response->assertOk();
    $statuses = collect($response->json('data'))->pluck('status');
    expect($statuses->filter(fn (string $status): bool => $status === 'assigned'))->toHaveCount(1);
    expect($statuses->filter(fn (string $status): bool => $status === 'submitted'))->toHaveCount(1);
});

it('reads the report row fields from the assigned realization', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create([
        'client_id' => $client->getKey(),
        'sim_id' => 'SIM-REAL-001',
        'full_name' => 'Karyawan Realisasi',
    ]);
    $realizationId = makeAssignedRealization($client, $admin, $employee, 'submitted');

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

it('hides the status filter from the work report page', function () {
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
    makeAssignedRealization($client, $admin, $employee, 'assigned');

    // An unassigned realization (no row in realization_employees) must not appear.
    DB::table('work_realizations')->insert([
        'client_id' => $client->getKey(), 'work_date' => now()->toDateString(), 'status' => 'draft', 'created_by' => $admin->getKey(),
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $response = $this->actingAs($admin)->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('reports.work').'?draw=1&start=0&length=10');

    $response->assertOk()->assertJsonCount(1, 'data');
});

it('filters the report by status instead of shift', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey()]);
    makeAssignedRealization($client, $admin, $employee, 'assigned');
    makeAssignedRealization($client, $admin, $employee, 'submitted');

    $pending = $this->actingAs($admin)->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('reports.work').'?draw=1&start=0&length=10&status=assigned');
    $pending->assertOk()->assertJsonCount(1, 'data');

    $done = $this->actingAs($admin)->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('reports.work').'?draw=1&start=0&length=10&status=submitted');
    $done->assertOk()->assertJsonCount(1, 'data');
});

it('does not treat an undefined hidden status filter as a real status', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey()]);
    makeAssignedRealization($client, $admin, $employee, 'submitted');

    $response = $this->actingAs($admin)->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('reports.work').'?draw=1&start=0&length=10&status=undefined');

    $response->assertOk()->assertJsonCount(1, 'data');
});

it('only reports products and work results from the active client', function () {
    $admin = User::factory()->superAdmin()->create();
    $activeClient = Client::factory()->create(['name' => 'PT Client Aktif']);
    $otherClient = Client::factory()->create(['name' => 'PT Client Lain']);
    $activeEmployee = Employee::factory()->create(['client_id' => $activeClient->getKey()]);
    $otherEmployee = Employee::factory()->create(['client_id' => $otherClient->getKey()]);

    makeAssignedRealization($activeClient, $admin, $activeEmployee, 'submitted');
    makeAssignedRealization($otherClient, $admin, $otherEmployee, 'submitted');

    $response = $this->actingAs($admin)
        ->withSession(['current_client_id' => $activeClient->getKey()])
        ->getJson(route('reports.work').'?draw=1&start=0&length=10');

    $response->assertOk()->assertJsonCount(1, 'data');
    expect($response->json('data.0.full_name'))->toBe($activeEmployee->full_name);
});
