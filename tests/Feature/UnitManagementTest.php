<?php

use App\Models\Client;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function unitClientContext(): array
{
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    return [$user, $client];
}

it('lists units for the current client', function () {
    [$user, $client] = unitClientContext();
    $unit = Unit::factory()->create(['client_id' => $client->getKey(), 'name' => 'Karton']);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->get(route('units.index'));

    $response->assertOk()->assertSee('Master Satuan');

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('units.index', ['draw' => 1, 'start' => 0, 'length' => 10]));

    $response->assertOk()->assertJsonFragment(['name' => $unit->name]);
});

it('creates a unit with validation and tenant scope', function () {
    [$user, $client] = unitClientContext();

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->post(route('units.store'), [
        'code' => 'PCS', 'name' => 'Pieces', 'status' => 'active',
    ]);

    $response->assertRedirect(route('units.index'))->assertSessionHas('status');
    $this->assertDatabaseHas('units', ['client_id' => $client->getKey(), 'code' => 'PCS', 'name' => 'Pieces']);
});

it('rejects duplicate unit codes within the same client', function () {
    [$user, $client] = unitClientContext();
    Unit::factory()->create(['client_id' => $client->getKey(), 'code' => 'PCS']);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->post(route('units.store'), [
        'code' => 'PCS', 'name' => 'Pieces', 'status' => 'active',
    ]);

    $response->assertInvalid(['code' => 'Kode satuan sudah digunakan pada client ini.']);
});

it('does not expose a unit from another client', function () {
    [$user, $client] = unitClientContext();
    $otherUnit = Unit::factory()->create(['name' => 'Rahasia']);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('units.index', ['draw' => 1, 'start' => 0, 'length' => 10]));

    $response->assertOk()->assertJsonMissing(['name' => $otherUnit->name]);
});

it('creates a unit via the ajax modal and returns json', function () {
    [$user, $client] = unitClientContext();

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->postJson(route('units.store'), [
        'code' => 'PCS', 'name' => 'Pieces', 'status' => 'active',
    ]);

    $response->assertCreated()->assertJsonPath('message', 'Satuan berhasil ditambahkan.');
    $this->assertDatabaseHas('units', ['client_id' => $client->getKey(), 'code' => 'PCS']);
});

it('returns validation errors as json for the ajax modal', function () {
    [$user, $client] = unitClientContext();

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->postJson(route('units.store'), [
        'code' => '', 'name' => 'Pieces', 'status' => 'active',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('code');
});

it('returns unit data as json for the edit modal', function () {
    [$user, $client] = unitClientContext();
    $unit = Unit::factory()->create(['client_id' => $client->getKey(), 'code' => 'PCS', 'name' => 'Pieces']);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->getJson(route('units.edit', $unit));

    $response->assertOk()->assertExactJson(['id' => $unit->getKey(), 'code' => 'PCS', 'name' => 'Pieces', 'status' => $unit->status]);
});

it('updates a unit via the ajax modal with method spoofing', function () {
    [$user, $client] = unitClientContext();
    $unit = Unit::factory()->create(['client_id' => $client->getKey(), 'code' => 'PCS', 'name' => 'Pieces']);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->postJson(route('units.update', $unit), [
        '_method' => 'PUT', 'code' => 'PCS', 'name' => 'Pieces Updated', 'status' => 'inactive',
    ]);

    $response->assertOk()->assertJsonPath('message', 'Satuan berhasil diperbarui.');
    $this->assertDatabaseHas('units', ['id' => $unit->getKey(), 'name' => 'Pieces Updated', 'status' => 'inactive']);
});

it('lists only active units for the current client as select options', function () {
    [$user, $client] = unitClientContext();
    $active = Unit::factory()->create(['client_id' => $client->getKey(), 'name' => 'Karton', 'status' => 'active']);
    Unit::factory()->create(['client_id' => $client->getKey(), 'name' => 'Nonaktif', 'status' => 'inactive']);
    $otherClientUnit = Unit::factory()->create(['name' => 'Rahasia', 'status' => 'active']);

    $response = $this->actingAs($user)->withSession(['current_client_id' => $client->getKey()])->getJson(route('units.options'));

    $response->assertOk()->assertExactJson(['results' => [['id' => $active->getKey(), 'text' => 'Karton']]]);
    $response->assertJsonMissing(['text' => 'Nonaktif'])->assertJsonMissing(['text' => $otherClientUnit->name]);
});
