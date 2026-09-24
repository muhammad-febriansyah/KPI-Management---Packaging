<?php

use App\Exports\DeductionExport;
use App\Exports\DeductionTemplateExport;
use App\Models\Client;
use App\Models\DeductionPeriod;
use App\Models\Employee;
use App\Models\EmployeeDeduction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

function buildDeductionImportFile(array $rows): UploadedFile
{
    $headings = ['Bulan', 'Minggu', 'No Karyawan', 'Nama Lengkap', 'Potongan Seragam', 'Potongan Perlengkapan', 'Potongan Uang Makan', 'BPJS Kesehatan Persen', 'BPJS Ketenagakerjaan Persen', 'Tipe DP Gaji', 'Nilai DP Gaji', 'Koreksi Pengurangan', 'Koreksi Penambahan', 'Catatan'];

    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray([$headings, ...$rows]);
    $path = tempnam(sys_get_temp_dir(), 'deduction-import').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'import.xlsx', null, null, true);
}

function buildTableDeductionImportFile(array $rows): UploadedFile
{
    $headings = ['Tanggal Input', 'Periode', 'SIM ID', 'Nama Lengkap', 'BPJS Kesehatan', 'BPJS Ketenagakerjaan', 'Koreksi Pengurangan', 'Koreksi Penambahan'];

    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray([$headings, ...$rows]);
    $path = tempnam(sys_get_temp_dir(), 'deduction-table-import').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'import-table.xlsx', null, null, true);
}

it('downloads a deduction export file', function () {
    Excel::fake();
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('deductions.export'))
        ->assertOk();

    Excel::matchByRegex();
    Excel::assertDownloaded('/^potongan-gaji-.*\.xlsx$/', fn (DeductionExport $export): bool => true);
});

it('downloads a deduction import template', function () {
    Excel::fake();
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    Employee::factory()->create(['client_id' => $client->getKey(), 'employee_no' => 'EMP001', 'full_name' => 'Ananda Julian']);

    $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('deductions.template'))
        ->assertOk();

    Excel::assertDownloaded('template-potongan-gaji.xlsx', function (DeductionTemplateExport $export): bool {
        return $export->headings() === ['Tanggal Input', 'Periode', 'SIM ID', 'Nama Lengkap', 'BPJS Kesehatan', 'BPJS Ketenagakerjaan', 'Koreksi Pengurangan', 'Koreksi Penambahan'];
    });
});

it('imports deduction rows using the visible table format', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create([
        'client_id' => $client->getKey(),
        'employee_no' => 'EMP001',
        'sim_id' => 'SIM-EMP001',
        'full_name' => 'Ananda Julian',
    ]);

    $file = buildTableDeductionImportFile([
        ['21/09/2026', 'September 2026 (Minggu 1)', 'SIM-EMP001', 'Ananda Julian', '1%', '2%', 'Rp 25.000', 'Rp 0'],
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->post(route('deductions.import'), ['file' => $file]);

    $response->assertOk()->assertJson(['message' => '1 baris potongan gaji berhasil diimpor.']);

    $period = DeductionPeriod::query()->where('client_id', $client->getKey())->where('week_no', 1)->firstOrFail();
    expect($period->month->format('Y-m'))->toBe('2026-09');

    $this->assertDatabaseHas('employee_deductions', [
        'deduction_period_id' => $period->getKey(),
        'employee_id' => $employee->getKey(),
        'bpjs_health_percent' => 1,
        'bpjs_employment_percent' => 2,
        'correction_minus' => 25000,
    ]);
});

it('imports deduction rows and creates the period and employee deduction', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'employee_no' => 'EMP001', 'full_name' => 'Ananda Julian']);

    $file = buildDeductionImportFile([
        ['2026-08', 2, 'EMP001', 'Ananda Julian', 50000, 25000, 10000, 1, 2, 'fixed', 100000, 0, 0, 'Catatan uji'],
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->post(route('deductions.import'), ['file' => $file]);

    $response->assertOk()->assertJson(['message' => '1 baris potongan gaji berhasil diimpor.']);

    $period = DeductionPeriod::query()->where('client_id', $client->getKey())->where('week_no', 2)->firstOrFail();
    expect($period->month->format('Y-m'))->toBe('2026-08');

    $this->assertDatabaseHas('employee_deductions', [
        'deduction_period_id' => $period->getKey(),
        'employee_id' => $employee->getKey(),
        'uniform_amount' => 50000,
        'salary_advance_type' => 'fixed',
        'notes' => 'Catatan uji',
    ]);
});

it('rejects deduction import rows beyond week two', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    Employee::factory()->create(['client_id' => $client->getKey(), 'employee_no' => 'EMP001', 'full_name' => 'Ananda Julian']);

    $file = buildDeductionImportFile([
        ['2026-08', 3, 'EMP001', 'Ananda Julian', 0, 0, 0, 0, 0, '', 0, 0, 0, ''],
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->post(route('deductions.import'), ['file' => $file]);

    $response->assertStatus(422)
        ->assertJsonPath('failures.0.row', 2);
    expect(DeductionPeriod::query()->where('client_id', $client->getKey())->exists())->toBeFalse();
});

it('reports per-row failures for unknown employees without failing the whole import', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'employee_no' => 'EMP001', 'full_name' => 'Ananda Julian']);

    $file = buildDeductionImportFile([
        ['2026-08', '', 'EMP001', 'Ananda Julian', 0, 0, 0, 0, 0, '', 0, 0, 0, ''],
        ['2026-08', '', 'EMP999', 'Karyawan Tidak Ada', 0, 0, 0, 0, 0, '', 0, 0, 0, ''],
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->post(route('deductions.import'), ['file' => $file]);

    $response->assertStatus(207);
    $response->assertJsonPath('failures.0.row', 3);
    expect(EmployeeDeduction::query()->where('employee_id', $employee->getKey())->exists())->toBeTrue();
    expect(EmployeeDeduction::query()->count())->toBe(1);
});
