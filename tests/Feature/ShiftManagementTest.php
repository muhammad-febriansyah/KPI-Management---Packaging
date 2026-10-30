<?php

use App\Models\Client;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the shift master for the current client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('shifts.index'));

    $response->assertOk()
        ->assertSee('name="target_client_id"', false)
        ->assertSee('Client');
});

it('creates a shift for the client selected by a super admin', function () {
    $user = User::factory()->superAdmin()->create();
    $currentClient = Client::factory()->create();
    $selectedClient = Client::factory()->create();

    $this->actingAs($user)
        ->withSession(['current_client_id' => $currentClient->getKey()])
        ->postJson(route('shifts.store'), [
            'target_client_id' => $selectedClient->getKey(),
            'code' => 'PAGI',
            'name' => 'Shift Pagi',
            'start_time' => '06:00',
            'end_time' => '14:00',
            'status' => 'active',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('shifts', [
        'client_id' => $selectedClient->getKey(),
        'code' => 'PAGI',
        'name' => 'Shift Pagi',
    ]);
});

it('moves a shift to another selected client when edited by a super admin', function () {
    $user = User::factory()->superAdmin()->create();
    $oldClient = Client::factory()->create();
    $newClient = Client::factory()->create();
    $shift = Shift::factory()->create(['client_id' => $oldClient->getKey()]);

    $this->actingAs($user)
        ->withSession(['current_client_id' => $oldClient->getKey()])
        ->putJson(route('shifts.update', ['shift' => $shift, 'client_id' => $oldClient->getKey()]), [
            'target_client_id' => $newClient->getKey(),
            'code' => $shift->code,
            'name' => 'Shift Dipindah',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'status' => 'active',
        ])
        ->assertOk();

    $this->assertDatabaseHas('shifts', [
        'id' => $shift->getKey(),
        'client_id' => $newClient->getKey(),
        'name' => 'Shift Dipindah',
    ]);
});

it('shows each shift client in the super admin list', function () {
    $user = User::factory()->superAdmin()->create();
    $currentClient = Client::factory()->create();
    $otherClient = Client::factory()->create(['code' => 'CL-SHIFT', 'name' => 'Client Shift']);
    Shift::factory()->create(['client_id' => $otherClient->getKey()]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $currentClient->getKey()])
        ->getJson(route('shifts.index', ['draw' => 1, 'start' => 0, 'length' => 10]));

    $response->assertOk()->assertJsonPath('data.0.client_name', 'CL-SHIFT — Client Shift');
    expect($response->json('data.0.action'))->toContain('&quot;target_client_id&quot;:'.$otherClient->getKey());
});

it('allows an assigned admin to manage the shift master for its client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();
    $role = Role::query()->where('code', 'admin')->firstOrFail();
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'status' => 'active']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('shifts.index'));

    $response->assertOk();
});

it('shows Master Shift in the admin navigation', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();
    $role = Role::query()->where('code', 'admin')->firstOrFail();
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'status' => 'active']);

    $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('shifts.index'), false)
        ->assertSee('Master Shift');
});
