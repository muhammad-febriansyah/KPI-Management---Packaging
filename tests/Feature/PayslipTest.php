<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makePayrollFixture(Client $client, User $user): Employee
{
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'employee_no' => 'EMP001', 'full_name' => 'Ananda Julian']);
    $shift = Shift::factory()->create(['client_id' => $client->getKey()]);
    $unit = Unit::factory()->create(['client_id' => $client->getKey()]);

    $productId = DB::table('products')->insertGetId([
        'client_id' => $client->getKey(), 'sku' => 'SKU1', 'name' => 'Produk 1', 'unit_id' => $unit->getKey(),
        'po_price' => 0, 'old_employee_rate' => 0, 'new_employee_rate' => 0, 'status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $realizationId = DB::table('work_realizations')->insertGetId([
        'client_id' => $client->getKey(), 'work_date' => '2026-08-10', 'shift_id' => $shift->getKey(), 'product_id' => $productId,
        'sku_snapshot' => 'SKU1', 'product_name_snapshot' => 'Produk 1', 'unit_name_snapshot' => 'PCS', 'total_output' => 100,
        'start_time' => '08:00', 'end_time' => '16:00', 'status' => 'draft', 'created_by' => $user->getKey(),
        'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('realization_employees')->insert([
        'client_id' => $client->getKey(), 'work_realization_id' => $realizationId, 'employee_id' => $employee->getKey(),
        'rate_category_snapshot' => 'baru', 'rate_per_unit_snapshot' => 100, 'gross_amount' => 500000, 'created_at' => now(),
    ]);

    return $employee;
}

it('downloads a single employee payslip pdf', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = makePayrollFixture($client, $user);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('reports.payroll.payslip', ['employee' => $employee->getKey(), 'date_from' => '2026-08-01', 'date_to' => '2026-08-31']));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF');
});

it('downloads a bulk payslip pdf for all employees in range', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    makePayrollFixture($client, $user);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('reports.payroll.payslip.bulk', ['date_from' => '2026-08-01', 'date_to' => '2026-08-31']));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

it('returns 404 for a payslip of an employee from another client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $otherEmployee = makePayrollFixture($otherClient, $user);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('reports.payroll.payslip', ['employee' => $otherEmployee->getKey()]));

    $response->assertNotFound();
});
