<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('finds employees and products matching the query for the current client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $unit = Unit::factory()->create(['client_id' => $client->id]);

    Employee::factory()->for($client)->create(['full_name' => 'Budi Santoso']);
    Employee::factory()->for($client)->create(['full_name' => 'Siti Aminah']);
    Product::create(['client_id' => $client->id, 'sku' => 'SKU-001', 'name' => 'Kardus Budi Jaya', 'unit_id' => $unit->id]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('search', ['q' => 'budi']));

    $response->assertOk();
    $response->assertJsonPath('employees.0.title', 'Budi Santoso');
    $response->assertJsonPath('products.0.title', 'Kardus Budi Jaya');
    $response->assertJsonCount(1, 'employees');
    $response->assertJsonCount(1, 'products');
});

it('does not return results from another client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();

    Employee::factory()->for($otherClient)->create(['full_name' => 'Budi Santoso']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('search', ['q' => 'budi']));

    $response->assertOk();
    $response->assertJsonCount(0, 'employees');
});

it('requires at least two characters before searching', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    Employee::factory()->for($client)->create(['full_name' => 'Budi Santoso']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('search', ['q' => 'b']));

    $response->assertOk();
    $response->assertJsonCount(0, 'employees');
});

it('matches static report shortcuts by title', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('search', ['q' => 'payroll']));

    $response->assertOk();
    $response->assertJsonPath('reports.0.title', 'Laporan Payroll');
});
