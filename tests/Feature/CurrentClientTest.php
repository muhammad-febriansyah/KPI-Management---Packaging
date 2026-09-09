<?php

use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use App\Services\CurrentClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Route::middleware(['web', 'auth', 'client'])
        ->get('/testing/current-client', fn (CurrentClientService $currentClient): array => [
            'client_id' => $currentClient->id(),
        ]);
});

it('stores an assigned active client in the session', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();
    $role = Role::factory()->create();
    $user->clients()->attach($client, [
        'role_id' => $role->getKey(),
        'is_default' => true,
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)
        ->from('/')
        ->put(route('current-client.update'), ['client_id' => $client->getKey()]);

    $response
        ->assertRedirect('/')
        ->assertSessionHas('current_client_id', $client->getKey())
        ->assertSessionHas('success', 'Client aktif berhasil diubah.');
});

it('forbids selecting an unassigned client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->put(route('current-client.update'), ['client_id' => $client->getKey()]);

    $response->assertForbidden();
});

it('rejects selecting an inactive client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->inactive()->create();

    $response = $this->actingAs($user)
        ->put(route('current-client.update'), ['client_id' => $client->getKey()]);

    $response->assertInvalid([
        'client_id' => 'Client yang dipilih tidak tersedia.',
    ]);
});

it('allows a super admin to select any active client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->put(route('current-client.update'), ['client_id' => $client->getKey()]);

    $response->assertSessionHas('current_client_id', $client->getKey());
});

it('resolves an assigned client for a protected request', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();
    $role = Role::factory()->create();
    $user->clients()->attach($client, [
        'role_id' => $role->getKey(),
        'is_default' => true,
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get('/testing/current-client');

    $response
        ->assertOk()
        ->assertJson(['client_id' => $client->getKey()]);
});

it('forbids protected requests without a current client', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/testing/current-client');

    $response->assertForbidden();
});

it('automatically selects the first active client for a super admin', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)->get('/testing/current-client');

    $response->assertOk()->assertJson(['client_id' => $client->getKey()]);
    expect(session('current_client_id'))->toBe($client->getKey());
});

it('clears an inaccessible current client from the session', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get('/testing/current-client');

    $response
        ->assertForbidden()
        ->assertSessionMissing('current_client_id');
});
