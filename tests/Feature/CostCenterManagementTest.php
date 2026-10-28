<?php

use App\Models\Client;
use App\Models\CostCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a cost center for the current client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->post(route('cost-centers.store'), ['client_id' => $client->getKey(), 'code' => 'CC-PACK', 'name' => 'Packing', 'status' => 'active']);
    $response->assertRedirect(route('cost-centers.index'));
    $this->assertDatabaseHas('cost_centers', ['client_id' => $client->getKey(), 'code' => 'CC-PACK']);
});

it('rejects duplicate cost center codes per client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    CostCenter::factory()->create(['client_id' => $client->getKey(), 'code' => 'CC-PACK']);
    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->post(route('cost-centers.store'), ['client_id' => $client->getKey(), 'code' => 'CC-PACK', 'name' => 'Other', 'status' => 'active']);
    $response->assertInvalid('code');
});

it('creates a cost center for a super admin selected client', function () {
    $user = User::factory()->superAdmin()->create();
    $activeClient = Client::factory()->create();
    $selectedClient = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $activeClient->getKey()])
        ->post(route('cost-centers.store'), [
            'client_id' => $selectedClient->getKey(),
            'code' => 'REPACK',
            'name' => 'Repack',
            'status' => 'active',
        ]);

    $response->assertRedirect(route('cost-centers.index'));
    $this->assertDatabaseHas('cost_centers', [
        'client_id' => $selectedClient->getKey(),
        'code' => 'REPACK',
    ]);
    $this->assertDatabaseMissing('cost_centers', [
        'client_id' => $activeClient->getKey(),
        'code' => 'REPACK',
    ]);
});

it('requires a selected client when a super admin creates a cost center', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('cost-centers.store'), [
            'code' => 'REPACK',
            'name' => 'Repack',
            'status' => 'active',
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['client_id' => 'Pilih client untuk cost center.']);
    $this->assertDatabaseMissing('cost_centers', ['code' => 'REPACK']);
});

it('lists active cost centers for the selected client as select options', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $active = CostCenter::factory()->create(['client_id' => $client->getKey(), 'name' => 'Packing', 'status' => 'active']);
    CostCenter::factory()->create(['client_id' => $client->getKey(), 'name' => 'Nonaktif', 'status' => 'inactive']);
    $otherClientCostCenter = CostCenter::factory()->create(['client_id' => $otherClient->getKey(), 'name' => 'Other Packing', 'status' => 'active']);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $otherClient->getKey()])->getJson(route('cost-centers.options', ['client_id' => $client->getKey()]));

    $response->assertOk()->assertExactJson(['results' => [
        ['id' => $active->getKey(), 'text' => 'Packing'],
    ]]);
    $response->assertJsonMissing(['text' => 'Nonaktif']);
    $response->assertJsonMissing(['id' => $otherClientCostCenter->getKey(), 'text' => 'Other Packing']);
});

it('lists cost centers from every client with client labels', function () {
    $user = User::factory()->superAdmin()->create();
    $firstClient = Client::factory()->create(['code' => 'CLIENT-A', 'name' => 'Client A']);
    $secondClient = Client::factory()->create(['code' => 'CLIENT-B', 'name' => 'Client B']);
    CostCenter::factory()->create(['client_id' => $firstClient->getKey(), 'code' => 'PACK-A']);
    CostCenter::factory()->create(['client_id' => $secondClient->getKey(), 'code' => 'PACK-B']);

    $this->actingAs($user)
        ->withSession(['current_client_id' => $firstClient->getKey()])
        ->getJson(route('cost-centers.index', ['draw' => 1, 'start' => 0, 'length' => 10]))
        ->assertOk()
        ->assertJsonFragment(['client_name' => 'CLIENT-A — Client A'])
        ->assertJsonFragment(['client_name' => 'CLIENT-B — Client B']);
});
