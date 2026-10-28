<?php

use App\Models\Client;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists and creates groups for the current client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->post(route('groups.store'), ['code' => 'PROD', 'name' => 'Produksi', 'status' => 'active']);

    $response->assertRedirect(route('groups.index'));
    $this->assertDatabaseHas('groups', ['client_id' => $client->getKey(), 'code' => 'PROD', 'name' => 'Produksi']);
});

it('rejects duplicate group codes within the same client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    Group::factory()->create(['client_id' => $client->getKey(), 'code' => 'PROD']);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->post(route('groups.store'), ['code' => 'PROD', 'name' => 'Lain', 'status' => 'active']);

    $response->assertInvalid(['code' => 'Kode group sudah digunakan pada client ini.']);
});

it('lists groups from every client for a super admin', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create(['code' => 'CST001', 'name' => 'Client Aktif']);
    $otherClient = Client::factory()->create(['code' => 'CST002', 'name' => 'Client Lain']);
    Group::factory()->create(['client_id' => $client->getKey(), 'name' => 'Group Terlihat']);
    $otherGroup = Group::factory()->create(['client_id' => $otherClient->getKey(), 'name' => 'Rahasia']);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('groups.index', ['draw' => 1, 'start' => 0, 'length' => 10]));

    $response->assertOk()
        ->assertJsonPath('data.0.client_name', 'CST001 — Client Aktif')
        ->assertJsonFragment(['name' => $otherGroup->name])
        ->assertJsonFragment(['client_name' => 'CST002 — Client Lain']);
});

it('lists active groups for the selected client as select options', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $active = Group::factory()->create(['client_id' => $client->getKey(), 'name' => 'Produksi', 'status' => 'active']);
    Group::factory()->create(['client_id' => $client->getKey(), 'name' => 'Nonaktif', 'status' => 'inactive']);
    $otherClientGroup = Group::factory()->create(['client_id' => $otherClient->getKey(), 'name' => 'Rahasia', 'status' => 'active']);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $otherClient->getKey()])->getJson(route('groups.options', ['client_id' => $client->getKey()]));

    $response->assertOk()->assertExactJson(['results' => [
        ['id' => $active->getKey(), 'text' => 'Produksi'],
    ]]);
    $response->assertJsonMissing(['text' => 'Nonaktif']);
    $response->assertJsonMissing(['id' => $otherClientGroup->getKey(), 'text' => 'Rahasia']);
});
