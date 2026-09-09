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
    $statuses = collect($response->json('data'))->pluck('work_status');
    expect($statuses->filter(fn (string $html): bool => str_contains($html, 'Belum dikerjakan')))->toHaveCount(1);
    expect($statuses->filter(fn (string $html): bool => str_contains($html, 'Sudah dikerjakan')))->toHaveCount(1);
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
