<?php

use App\Models\Batch;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use App\Models\WorkRealization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validRealizationPayload(array $overrides = []): array
{
    $client = $overrides['client'];
    unset($overrides['client']);

    $unit = Unit::factory()->create(['client_id' => $client->id]);
    $product = Product::create(['client_id' => $client->id, 'sku' => 'SKU-'.fake()->unique()->numerify('####'), 'name' => 'Produk Uji', 'unit_id' => $unit->id]);
    $batch = Batch::create(['client_id' => $client->id, 'batch_no' => 'BATCH-'.fake()->unique()->numerify('####'), 'product_id' => $product->id]);
    $shift = Shift::factory()->create(['client_id' => $client->id]);

    return array_merge([
        'work_date' => now()->toDateString(),
        'shift_id' => $shift->id,
        'batch_id' => $batch->id,
        'product_id' => $product->id,
        'total_output' => 100,
        'start_time' => '08:00',
        'end_time' => '17:00',
    ], $overrides);
}

function linkedEmployee(Client $client): array
{
    $employeeUser = User::factory()->create();
    $role = Role::query()->firstOrCreate(['code' => 'employee'], ['name' => 'Karyawan']);
    $employeeUser->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $employeeUser->getKey()]);

    return [$employeeUser, $employee];
}

it('sanitizes the report field before storing it', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    [$employeeUser, $employee] = linkedEmployee($client);

    $payload = validRealizationPayload([
        'client' => $client,
        'employee_ids' => [$employee->getKey()],
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), $payload);

    $response->assertCreated();

    $realization = WorkRealization::query()->where('client_id', $client->id)->firstOrFail();
    $response = $this->actingAs($employeeUser)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('realizations.update', $realization), [
            'total_output' => 100,
            'start_time' => '08:00',
            'end_time' => '17:00',
            'report' => '<p>Hasil <strong>bagus</strong></p><script>alert("xss")</script>',
        ]);

    $response->assertOk();
    $realization->refresh();
    expect($realization->report)
        ->toContain('<p>Hasil <strong>bagus</strong></p>')
        ->not->toContain('<script>')
        ->not->toContain('alert');
});

it('stores a null report when nothing was written', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    [$employeeUser, $employee] = linkedEmployee($client);

    $payload = validRealizationPayload(['client' => $client, 'employee_ids' => [$employee->getKey()]]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), $payload);

    $response->assertCreated();

    $realization = WorkRealization::query()->where('client_id', $client->id)->firstOrFail();
    $response = $this->actingAs($employeeUser)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('realizations.update', $realization), [
            'total_output' => 100,
            'start_time' => '08:00',
            'end_time' => '17:00',
            'report' => '',
        ]);

    $response->assertOk();
    $realization->refresh();
    expect($realization->report)->toBeNull();
});
