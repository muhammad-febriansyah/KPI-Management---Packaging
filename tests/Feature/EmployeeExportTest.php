<?php

use App\Exports\EmployeeExport;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

it('downloads a formal employee master Excel report for the active client', function () {
    Excel::fake();
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create(['code' => 'CL-EXPORT', 'name' => 'PT Export Client']);
    $group = Group::factory()->create(['client_id' => $client->getKey(), 'name' => 'Packing']);
    $employee = Employee::factory()->create([
        'client_id' => $client->getKey(),
        'group_id' => $group->getKey(),
        'full_name' => 'Ananda Julian',
        'join_date' => '2026-09-01',
        'birth_date' => '1995-01-01',
        'gender' => 'male',
        'employee_status' => 'permanent',
        'marital_status' => 'single',
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('employees.export'));

    $response->assertOk();
    Excel::matchByRegex();
    Excel::assertDownloaded('/^master-karyawan-CL-EXPORT-.*\.xlsx$/', function (EmployeeExport $export) use ($employee): bool {
        return $export->headings() === ['No', 'ID Karyawan', 'SIM ID', 'Nama Lengkap', 'Email', 'Nomor Telepon', 'Tanggal Masuk', 'Tanggal Lahir', 'Jenis Kelamin', 'Status Karyawan', 'Status Perkawinan', 'Group', 'Status']
            && $export->query()->count() === 1
            && $export->map($employee)[3] === 'Ananda Julian';
    });

    $this->get(route('employees.index'))
        ->assertOk()
        ->assertSee('Export Excel', false)
        ->assertSee(route('employees.export'), false);
});
