<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use App\Models\WorkRealization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('saves a realization when the assignment row was left blank', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $shift = Shift::factory()->create(['client_id' => $client->getKey()]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), [
            'work_date' => '2026-09-09',
            'shift_id' => $shift->getKey(),
            'employee_ids' => [''],
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('work_realizations', ['client_id' => $client->getKey(), 'shift_id' => $shift->getKey()]);
    $this->assertDatabaseEmpty('realization_employees');
});

it('keeps the employees that were filled in when a later row is blank', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $shift = Shift::factory()->create(['client_id' => $client->getKey()]);
    $assignedUser = User::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $assignedUser->getKey()]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), [
            'work_date' => '2026-09-09',
            'shift_id' => $shift->getKey(),
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
