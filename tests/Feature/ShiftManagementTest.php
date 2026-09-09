<?php

use App\Models\Client;
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
