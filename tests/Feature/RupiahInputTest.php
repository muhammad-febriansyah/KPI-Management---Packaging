<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the rupiah input component on the product master page', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('products.index'));

    $response->assertOk();
    $response->assertSee('data-rupiah-field', false);
    $response->assertSee('name="po_price"', false);
});

it('renders the rupiah input component on the deductions page', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('deductions.index'));

    $response->assertOk();
    $response->assertSee('data-rupiah-field', false);
    $response->assertSee('name="salary_advance_value"', false);
});
