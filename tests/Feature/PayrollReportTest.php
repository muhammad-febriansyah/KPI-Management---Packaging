<?php

use App\Exports\PayrollReportExport;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

function makePayrollReportFixture(Client $client, User $user): Employee
{
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'employee_no' => 'EMP001', 'full_name' => 'Ananda Julian']);
    $shift = Shift::factory()->create(['client_id' => $client->getKey()]);
    $unit = Unit::factory()->create(['client_id' => $client->getKey()]);
    $productId = DB::table('products')->insertGetId([
        'client_id' => $client->getKey(), 'sku' => 'SKU1', 'name' => 'Produk 1', 'unit_id' => $unit->getKey(),
        'po_price' => 0, 'employee_rate' => 0, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $realizationId = DB::table('work_realizations')->insertGetId([
        'client_id' => $client->getKey(), 'work_date' => '2026-08-10', 'shift_id' => $shift->getKey(), 'product_id' => $productId,
        'sku_snapshot' => 'SKU1', 'product_name_snapshot' => 'Produk 1', 'unit_name_snapshot' => 'PCS', 'total_output' => 100,
        'start_time' => '08:00', 'end_time' => '16:00', 'created_by' => $user->getKey(), 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('realization_employees')->insert([
        'client_id' => $client->getKey(), 'work_realization_id' => $realizationId, 'employee_id' => $employee->getKey(),
        'rate_category_snapshot' => 'baru', 'rate_per_unit_snapshot' => 100, 'gross_amount' => 500000, 'created_at' => now(),
    ]);

    return $employee;
}

it('renders the payroll report page with a default current-month date range', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('reports.payroll'));

    $response->assertOk();
    $response->assertViewHas('dateFrom', now()->startOfMonth()->toDateString());
    $response->assertViewHas('dateTo', now()->endOfMonth()->toDateString());
    $response->assertSee('data-payroll-export-excel');
    $response->assertSee('data-payroll-export-pdf');
    $response->assertSee('<th>NIK</th>', false);
    $response->assertSee('<th>Gaji bersih</th>', false);
    $response->assertSee('<th>Gaji kotor</th>', false);
    $response->assertSee('<th>BPJS Kesehatan</th>', false);
    $response->assertSee('<th>BPJS Ketenagakerjaan</th>', false);
    $response->assertSee('<th>Seragam (Kaos/Celana)</th>', false);
    $response->assertDontSee('<th>Aksi</th>', false);
    $response->assertDontSee('Slip gaji');
});

it('uses the requirement order for payroll export columns', function () {
    $export = new PayrollReportExport(1, '2026-09-01', '2026-09-30');

    expect($export->headings())->toBe([
        'No',
        'NIK',
        'Nama Lengkap',
        'Jenis Kelamin',
        'Total Hari Masuk',
        'Gaji Bersih',
        'Gaji Kotor',
        'BPJS Kesehatan',
        'BPJS Ketenagakerjaan',
        'Seragam (Kaos/Celana)',
        'Perlengkapan Kerja',
        'Uang Makan',
        'DP Gaji',
        'Koreksi Pengurangan',
        'Koreksi Penambahan',
    ]);
});

it('subtracts health and employment BPJS from gross salary', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = makePayrollReportFixture($client, $user);
    $period = DB::table('deduction_periods')->insertGetId([
        'client_id' => $client->getKey(), 'month' => '2026-08-01', 'status' => 'locked',
        'created_by' => $user->getKey(), 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('employee_deductions')->insert([
        'client_id' => $client->getKey(), 'deduction_period_id' => $period, 'employee_id' => $employee->getKey(),
        'bpjs_health_percent' => 1, 'bpjs_employment_percent' => 2,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('reports.payroll').'?draw=1&start=0&length=10&date_from=2026-08-01&date_to=2026-08-31');

    $response->assertOk();
    $response->assertJsonPath('data.0.bpjs_health', 5000);
    $response->assertJsonPath('data.0.bpjs_employment', 10000);
    $response->assertJsonPath('data.0.net_salary', 485000);
});

it('searches the payroll report by employee name', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    makePayrollReportFixture($client, $user);
    $columns = array_map(
        fn (string $column): array => ['data' => $column, 'searchable' => 'true', 'orderable' => 'true', 'search' => ['value' => '', 'regex' => 'false']],
        ['employee_no', 'full_name', 'gender', 'attendance_days', 'net_salary', 'gross_salary', 'bpjs_health', 'bpjs_employment', 'uniform_amount', 'equipment_amount', 'meal_amount', 'salary_advance_value', 'correction_minus', 'correction_plus'],
    );

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('reports.payroll').'?'.http_build_query([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
            'search' => ['value' => 'ananda', 'regex' => 'false'],
            'columns' => $columns,
            'order' => [['column' => 1, 'dir' => 'asc']],
        ]));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.full_name', 'Ananda Julian');
});

it('downloads the payroll report as an Excel file for the selected period', function () {
    Excel::fake();
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('reports.payroll.export.excel', ['date_from' => '2026-08-01', 'date_to' => '2026-08-31']))
        ->assertOk();

    Excel::assertDownloaded('laporan-payroll-2026-08-01-2026-08-31.xlsx', function (PayrollReportExport $export): bool {
        return true;
    });
});

it('downloads the payroll report as a PDF file for the selected period', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('reports.payroll.export.pdf', ['date_from' => '2026-08-01', 'date_to' => '2026-08-31']));

    $response->assertOk();
    expect($response->getContent())->toStartWith('%PDF');
});

it('filters payroll attendance and gross salary by the selected date range', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'employee_no' => 'EMP001', 'full_name' => 'Ananda Julian']);
    $shift = Shift::factory()->create(['client_id' => $client->getKey()]);
    $unit = Unit::factory()->create(['client_id' => $client->getKey()]);

    $productId = DB::table('products')->insertGetId([
        'client_id' => $client->getKey(), 'sku' => 'SKU1', 'name' => 'Produk 1', 'unit_id' => $unit->getKey(),
        'po_price' => 0, 'employee_rate' => 0, 'status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $insideRealizationId = DB::table('work_realizations')->insertGetId([
        'client_id' => $client->getKey(), 'work_date' => '2026-08-10', 'shift_id' => $shift->getKey(), 'product_id' => $productId,
        'sku_snapshot' => 'SKU1', 'product_name_snapshot' => 'Produk 1', 'unit_name_snapshot' => 'PCS', 'total_output' => 100,
        'start_time' => '08:00', 'end_time' => '16:00', 'created_by' => $user->getKey(),
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $outsideRealizationId = DB::table('work_realizations')->insertGetId([
        'client_id' => $client->getKey(), 'work_date' => '2026-09-10', 'shift_id' => $shift->getKey(), 'product_id' => $productId,
        'sku_snapshot' => 'SKU1', 'product_name_snapshot' => 'Produk 1', 'unit_name_snapshot' => 'PCS', 'total_output' => 100,
        'start_time' => '08:00', 'end_time' => '16:00', 'created_by' => $user->getKey(),
        'created_at' => now(), 'updated_at' => now(),
    ]);

    DB::table('realization_employees')->insert([
        ['client_id' => $client->getKey(), 'work_realization_id' => $insideRealizationId, 'employee_id' => $employee->getKey(), 'rate_category_snapshot' => 'baru', 'rate_per_unit_snapshot' => 100, 'gross_amount' => 50000, 'created_at' => now()],
        ['client_id' => $client->getKey(), 'work_realization_id' => $outsideRealizationId, 'employee_id' => $employee->getKey(), 'rate_category_snapshot' => 'baru', 'rate_per_unit_snapshot' => 100, 'gross_amount' => 70000, 'created_at' => now()],
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('reports.payroll').'?draw=1&start=0&length=10&date_from=2026-08-01&date_to=2026-08-31');

    $response->assertOk();
    $response->assertJsonPath('data.0.employee_no', 'EMP001');
    $response->assertJsonPath('data.0.attendance_days', 1);
    // MySQL returns SUM() over a BIGINT column as a decimal string, so compare the amount
    // numerically: the contract is that only the in-range 50000 row counts, not its PHP type.
    expect((int) $response->json('data.0.gross_salary'))->toBe(50000);
});
