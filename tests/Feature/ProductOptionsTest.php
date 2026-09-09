<?php

use App\Models\Client;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('includes unit name and estimated output per hour so the realization form can preview them', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $unit = Unit::factory()->create(['client_id' => $client->id, 'name' => 'Karton']);
    Product::create([
        'client_id' => $client->id,
        'sku' => 'SKU-001',
        'name' => 'Kopi Sachet 25g',
        'unit_id' => $unit->id,
        'estimated_output_per_hour' => 500,
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('products.options', ['q' => 'Kopi']));

    $response->assertOk();
    $response->assertJsonPath('results.0.unit_name', 'Karton');
    $response->assertJsonPath('results.0.estimated_output_per_hour', 500);
});

it('does not return products from another client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $otherUnit = Unit::factory()->create(['client_id' => $otherClient->id]);
    Product::create(['client_id' => $otherClient->id, 'sku' => 'SKU-999', 'name' => 'Produk Lain', 'unit_id' => $otherUnit->id]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('products.options'));

    $response->assertOk();
    $response->assertJsonCount(0, 'results');
});
