<?php

use App\Exports\ClientTemplateExport;
use App\Exports\EmployeeTemplateExport;
use App\Exports\GroupTemplateExport;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

function buildMasterImportFile(array $headings, array $rows, string $prefix): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray([$headings, ...$rows]);
    $path = tempnam(sys_get_temp_dir(), $prefix).'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'import.xlsx', null, null, true);
}

it('shows import controls on employee, group, and client master pages', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()]);
    $this->get(route('employees.index'))->assertOk()->assertSee('data-employee-import-open', false)->assertSee(route('employees.template'), false);
    $this->get(route('groups.index'))->assertOk()->assertSee('data-group-import-open', false)->assertSee(route('groups.template'), false);
    $this->get(route('clients.index'))->assertOk()->assertSee('data-client-import-open', false)->assertSee(route('clients.template'), false);
});

it('downloads code-based templates for employee, group, and client', function () {
    Excel::fake();
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create(['code' => 'CL-MASTER']);
    $group = Group::factory()->create(['client_id' => $client->getKey(), 'code' => 'GRP-01']);
    $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()]);

    $this->get(route('employees.template'))->assertOk();
    $this->get(route('groups.template'))->assertOk();
    $this->get(route('clients.template'))->assertOk();

    Excel::assertDownloaded('template-master-karyawan.xlsx', fn (EmployeeTemplateExport $export): bool => $export->headings()[0] === 'Client Code' && $export->headings()[10] === 'Group Code' && $export->array()[0][10] === $group->code);
    Excel::assertDownloaded('template-master-group.xlsx', fn (GroupTemplateExport $export): bool => $export->headings() === ['Client Code', 'Group Code', 'Nama Group', 'Status']);
    Excel::assertDownloaded('template-master-client.xlsx', fn (ClientTemplateExport $export): bool => $export->headings() === ['Client Code', 'Nama Client', 'Timezone', 'Status']);
});

it('imports clients using client code as upsert key', function () {
    $user = User::factory()->superAdmin()->create();
    $existing = Client::factory()->create(['code' => 'CL-001', 'name' => 'Nama Lama']);
    $file = buildMasterImportFile(['Client Code', 'Nama Client', 'Timezone', 'Status'], [
        ['CL-001', 'Nama Baru', 'Asia/Jakarta', 'active'],
        ['CL-002', 'Client Baru', 'Asia/Makassar', 'active'],
    ], 'client-import');

    $response = $this->actingAs($user)->post(route('clients.import'), ['file' => $file]);

    $response->assertOk()->assertJson(['message' => '2 baris client berhasil diimpor.']);
    $this->assertDatabaseHas('clients', ['id' => $existing->getKey(), 'name' => 'Nama Baru']);
    $this->assertDatabaseHas('clients', ['code' => 'CL-002', 'name' => 'Client Baru', 'timezone' => 'Asia/Makassar']);
});

it('imports groups and employees by client and group codes', function () {
    $user = User::factory()->superAdmin()->create();
    $currentClient = Client::factory()->create(['code' => 'CL-CURRENT']);
    $targetClient = Client::factory()->create(['code' => 'CL-TARGET']);
    $groupFile = buildMasterImportFile(['Client Code', 'Group Code', 'Nama Group', 'Status'], [
        [$targetClient->code, 'PACK', 'Packing', 'active'],
    ], 'group-import');

    $this->actingAs($user)->withSession(['current_client_id' => $currentClient->getKey()])
        ->post(route('groups.import'), ['file' => $groupFile])
        ->assertOk()->assertJson(['message' => '1 baris group berhasil diimpor.']);

    $group = Group::query()->withoutGlobalScopes()->where('client_id', $targetClient->getKey())->where('code', 'PACK')->firstOrFail();
    $employeeFile = buildMasterImportFile(['Client Code', 'SIM ID', 'Nama Lengkap', 'Email', 'Nomor Telepon', 'Tanggal Masuk', 'Tanggal Lahir', 'Jenis Kelamin', 'Status Karyawan', 'Status Perkawinan', 'Group Code', 'Status'], [
        [$targetClient->code, 'SIM-001', 'Karyawan Satu', 'karyawan.satu@example.com', '081234567890', '2026-09-01', '1995-01-01', 'male', 'permanent', 'single', $group->code, 'active'],
    ], 'employee-import');

    $this->post(route('employees.import'), ['file' => $employeeFile])
        ->assertOk()->assertJson(['message' => '1 baris karyawan berhasil diimpor.']);

    $employee = Employee::query()->withoutGlobalScopes()->where('client_id', $targetClient->getKey())->where('sim_id', 'SIM-001')->firstOrFail();
    expect($employee->group_id)->toBe($group->getKey());
    $this->assertDatabaseHas('users', ['id' => $employee->user_id, 'username' => $employee->employee_no, 'email' => 'karyawan.satu@example.com']);
    $this->assertDatabaseHas('client_user', ['client_id' => $targetClient->getKey(), 'user_id' => $employee->user_id, 'status' => 'active']);
});

it('reports invalid group codes without discarding valid employee rows', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create(['code' => 'CL-MASTER']);
    $file = buildMasterImportFile(['Client Code', 'SIM ID', 'Nama Lengkap', 'Email', 'Nomor Telepon', 'Tanggal Masuk', 'Tanggal Lahir', 'Jenis Kelamin', 'Status Karyawan', 'Status Perkawinan', 'Group Code', 'Status'], [
        [$client->code, 'SIM-VALID', 'Karyawan Valid', '', '081234567890', '2026-09-01', '1995-01-01', 'male', 'permanent', 'single', '', 'active'],
        [$client->code, 'SIM-INVALID', 'Karyawan Invalid', '', '081234567891', '2026-09-01', '1995-01-01', 'male', 'permanent', 'single', 'UNKNOWN', 'active'],
    ], 'employee-invalid-import');

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->post(route('employees.import'), ['file' => $file]);

    $response->assertStatus(207)->assertJsonPath('failures.0.row', 3);
    expect($response->json('failures.0.errors.0'))->toContain('Group dengan kode "UNKNOWN"');
    $this->assertDatabaseHas('employees', ['client_id' => $client->getKey(), 'sim_id' => 'SIM-VALID']);
    $this->assertDatabaseMissing('employees', ['client_id' => $client->getKey(), 'sim_id' => 'SIM-INVALID']);
});
