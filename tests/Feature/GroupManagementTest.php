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

it('keeps groups isolated by client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $otherGroup = Group::factory()->create(['name' => 'Rahasia']);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('groups.index', ['draw' => 1, 'start' => 0, 'length' => 10]));

    $response->assertOk()->assertJsonMissing(['name' => $otherGroup->name]);
});

it('lists only active groups for the current client as select options', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $active = Group::factory()->create(['client_id' => $client->getKey(), 'name' => 'Produksi', 'status' => 'active']);
    Group::factory()->create(['client_id' => $client->getKey(), 'name' => 'Nonaktif', 'status' => 'inactive']);
    $otherClientGroup = Group::factory()->create(['name' => 'Rahasia', 'status' => 'active']);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->getJson(route('groups.options'));

    $response->assertOk()->assertExactJson(['results' => [['id' => $active->getKey(), 'text' => 'Produksi']]]);
    $response->assertJsonMissing(['text' => 'Nonaktif'])->assertJsonMissing(['text' => $otherClientGroup->name]);
});
