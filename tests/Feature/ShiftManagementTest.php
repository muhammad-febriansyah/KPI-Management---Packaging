<?php

use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the shift master for the current client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('shifts.index'));

    $response->assertOk();
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
