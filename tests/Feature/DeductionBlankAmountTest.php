<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeDeduction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores a deduction when the optional amounts are left blank', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey()]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('deductions.store'), [
            'month' => '2026-09',
            'uniform_amount' => '10000',
            'equipment_amount' => '',
            'meal_amount' => '',
            'bpjs_health_percent' => '',
            'bpjs_employment_percent' => '',
            'salary_advance_value' => '',
            'correction_minus' => '',
            'correction_plus' => '',
            'employee_ids' => [$employee->getKey()],
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('employee_deductions', [
        'employee_id' => $employee->getKey(),
        'uniform_amount' => 10000,
        'equipment_amount' => 0,
        'meal_amount' => 0,
    ]);
});

it('updates a deduction when an amount is cleared', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey()]);

    $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('deductions.store'), [
            'month' => '2026-09',
            'uniform_amount' => '10000',
            'employee_ids' => [$employee->getKey()],
        ])->assertCreated();

    $deduction = EmployeeDeduction::query()->firstOrFail();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('deductions.update', $deduction), [
            'uniform_amount' => '',
            'equipment_amount' => '2500',
        ]);

    $response->assertOk();
    $this->assertDatabaseHas('employee_deductions', [
        'id' => $deduction->getKey(),
        'uniform_amount' => 0,
        'equipment_amount' => 2500,
    ]);
});
