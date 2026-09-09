<?php

use App\Models\Client;
use App\Models\CostCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a cost center for the current client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->post(route('cost-centers.store'), ['code' => 'CC-PACK', 'name' => 'Packing', 'status' => 'active']);
    $response->assertRedirect(route('cost-centers.index'));
    $this->assertDatabaseHas('cost_centers', ['client_id' => $client->getKey(), 'code' => 'CC-PACK']);
});

it('rejects duplicate cost center codes per client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    CostCenter::factory()->create(['client_id' => $client->getKey(), 'code' => 'CC-PACK']);
    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->post(route('cost-centers.store'), ['code' => 'CC-PACK', 'name' => 'Other', 'status' => 'active']);
    $response->assertInvalid('code');
});

it('lists only active cost centers for the current client as select options', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $active = CostCenter::factory()->create(['client_id' => $client->getKey(), 'name' => 'Packing', 'status' => 'active']);
    CostCenter::factory()->create(['client_id' => $client->getKey(), 'name' => 'Nonaktif', 'status' => 'inactive']);
    $otherClientCostCenter = CostCenter::factory()->create(['name' => 'Rahasia', 'status' => 'active']);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->getJson(route('cost-centers.options'));

    $response->assertOk()->assertExactJson(['results' => [['id' => $active->getKey(), 'text' => 'Packing']]]);
    $response->assertJsonMissing(['text' => 'Nonaktif'])->assertJsonMissing(['text' => $otherClientCostCenter->name]);
});
