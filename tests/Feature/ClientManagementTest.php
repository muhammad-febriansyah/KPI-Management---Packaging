<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('redirects guests to login from the root page', function () {
    $response = $this->get('/');

    $response->assertRedirectToRoute('login');
});

it('lists clients as json for the datatable', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create(['name' => 'PT Sumber Makmur']);

    $response = $this->actingAs($admin)->getJson(route('clients.index', ['draw' => 1, 'start' => 0, 'length' => 10]));

    $response->assertOk()->assertJsonFragment(['name' => 'PT Sumber Makmur']);
});

it('lets a super admin create a client via the ajax modal', function () {
    $admin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($admin)->postJson(route('clients.store'), [
        'code' => 'KP-001', 'name' => 'PT Contoh', 'timezone' => 'Asia/Jakarta', 'status' => 'active',
    ]);

    $response->assertCreated()->assertJsonPath('message', 'Client berhasil ditambahkan.');
    $this->assertDatabaseHas('clients', ['code' => 'KP-001', 'name' => 'PT Contoh']);
});

it('rejects a non super admin from creating a client', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('clients.store'), [
        'code' => 'KP-001', 'name' => 'PT Contoh', 'timezone' => 'Asia/Jakarta', 'status' => 'active',
    ]);

    $response->assertForbidden();
});

it('updates a client via the ajax modal with method spoofing', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create(['name' => 'Nama Lama']);

    $response = $this->actingAs($admin)->postJson(route('clients.update', $client), [
        '_method' => 'PUT', 'code' => $client->code, 'name' => 'Nama Baru', 'timezone' => $client->timezone, 'status' => 'active',
    ]);

    $response->assertOk()->assertJsonPath('message', 'Client berhasil diperbarui.');
    $this->assertDatabaseHas('clients', ['id' => $client->getKey(), 'name' => 'Nama Baru']);
});

it('deletes a client via ajax', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($admin)->deleteJson(route('clients.destroy', $client));

    $response->assertOk()->assertJsonPath('message', 'Client berhasil dihapus.');
    $this->assertSoftDeleted('clients', ['id' => $client->getKey()]);
});
