<?php

use App\Models\Client;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a translated message instead of the raw translation key', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('shifts.store'), []);

    $response->assertUnprocessable();
    expect($response->json('message'))->not->toContain('validation.');
    $response->assertJsonPath('errors.code.0', 'kode wajib diisi.');
});

it('accepts a time that still carries seconds, as the edit form submits it', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $shift = Shift::factory()->create(['client_id' => $client->getKey()]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('shifts.update', $shift), [
            'code' => $shift->code,
            'name' => 'Shift Diubah',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'status' => 'active',
        ]);

    $response->assertOk();
    $this->assertDatabaseHas('shifts', ['id' => $shift->getKey(), 'name' => 'Shift Diubah', 'start_time' => '08:00:00']);
});

it('rejects a time that is not a clock value', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $shift = Shift::factory()->create(['client_id' => $client->getKey()]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('shifts.update', $shift), [
            'code' => $shift->code,
            'name' => 'Shift Diubah',
            'start_time' => 'pagi',
            'end_time' => '16:00',
            'status' => 'active',
        ]);

    $response->assertUnprocessable();
    $response->assertJsonPath('errors.start_time.0', 'Format jam mulai tidak sesuai dengan H:i.');
});
