<?php

use App\Models\Client;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
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
        'employee_rate' => 12,
        'estimated_output_per_hour' => 500,
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('products.options', ['q' => 'Kopi']));

    $response->assertOk();
    $response->assertJsonPath('results.0.unit_name', 'Karton');
    $response->assertJsonPath('results.0.employee_rate', '12.000');
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

it('lets an employee read product options for a realization', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();
    $role = Role::query()->create(['code' => 'employee', 'name' => 'Karyawan']);
    $permission = Permission::query()->create(['code' => 'menu.realizations', 'name' => 'Menu: Realisasi']);
    $role->permissions()->attach($permission);
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);
    $unit = Unit::factory()->create(['client_id' => $client->id]);
    Product::create(['client_id' => $client->id, 'sku' => 'SKU-EMP-001', 'name' => 'Produk Karyawan', 'unit_id' => $unit->id]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('products.options'));

    $response->assertOk()->assertJsonPath('results.0.text', 'SKU-EMP-001 — Produk Karyawan');
});

it('returns searchable client options only within the user access boundary', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create(['code' => 'CLI-001', 'name' => 'Client Satu']);
    Client::factory()->create(['code' => 'CLI-002', 'name' => 'Client Dua']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('clients.options', ['q' => 'Client Satu']));

    $response->assertOk()
        ->assertJsonCount(1, 'results')
        ->assertJsonPath('results.0.text', 'CLI-001 — Client Satu');
});

it('limits a non-superadmin client selector to the active client', function () {
    $user = User::factory()->create();
    $activeClient = Client::factory()->create(['code' => 'CLI-ACTIVE', 'name' => 'Client Aktif']);
    $otherClient = Client::factory()->create(['code' => 'CLI-OTHER', 'name' => 'Client Lain']);
    $role = Role::query()->create(['code' => 'client-options', 'name' => 'Client Options']);
    $user->clients()->attach($activeClient, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $activeClient->getKey()])
        ->getJson(route('clients.options', ['q' => 'Client']));

    $response->assertOk()
        ->assertJsonCount(1, 'results')
        ->assertJsonPath('results.0.id', $activeClient->getKey());
    expect($response->json('results.0.id'))->not->toBe($otherClient->getKey());
});
