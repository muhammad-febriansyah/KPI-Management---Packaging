<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use App\Models\WorkRealization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects a realization when its required assignment row was left blank', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $shift = Shift::factory()->create(['client_id' => $client->getKey()]);
    $unit = Unit::factory()->create(['client_id' => $client->getKey()]);
    $product = Product::factory()->create(['client_id' => $client->getKey(), 'unit_id' => $unit->getKey()]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), [
            'work_date' => '2026-09-09',
            'batch_no' => 'B-20260909-0001',
            'shift_id' => $shift->getKey(),
            'product_id' => $product->getKey(),
            'total_output' => 10,
            'start_time' => '08:00',
            'end_time' => '16:00',
            'report' => 'Catatan pekerjaan.',
            'employee_ids' => [''],
        ]);

    $response->assertJsonValidationErrors('employee_ids');
    $this->assertDatabaseEmpty('work_realizations');
    $this->assertDatabaseEmpty('realization_employees');
});

it('keeps the employees that were filled in when a later row is blank', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $shift = Shift::factory()->create(['client_id' => $client->getKey()]);
    $unit = Unit::factory()->create(['client_id' => $client->getKey()]);
    $product = Product::factory()->create(['client_id' => $client->getKey(), 'unit_id' => $unit->getKey()]);
    $assignedUser = User::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $assignedUser->getKey()]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), [
            'work_date' => '2026-09-09',
            'batch_no' => 'B-20260909-0001',
            'shift_id' => $shift->getKey(),
            'product_id' => $product->getKey(),
            'total_output' => 10,
            'start_time' => '08:00',
            'end_time' => '16:00',
            'report' => 'Catatan pekerjaan.',
            'employee_ids' => [(string) $employee->getKey(), ''],
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('realization_employees', ['employee_id' => $employee->getKey()]);
});

it('rejects an assignment that names no employee at all', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $realization = WorkRealization::query()->create([
        'client_id' => $client->getKey(),
        'work_date' => '2026-09-09',
        'created_by' => $user->getKey(),
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.assign', $realization), ['employee_ids' => ['']]);

    $response->assertUnprocessable();
    $response->assertJsonPath('errors.employee_ids.0', 'karyawan wajib diisi.');
});
