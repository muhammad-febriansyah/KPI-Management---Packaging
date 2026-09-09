<?php

use App\Models\Client;
use App\Models\CostCenter;
use App\Models\Group;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps the product index responsive on narrow screens', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('products.index'));

    $response->assertOk()
        ->assertSee('data-product-table-wrapper', false)
        ->assertSee('overflow-x-auto', false)
        ->assertSee('min-w-[720px]', false)
        ->assertSee('flex-col', false)
        ->assertSee('w-full', false);
});

it('includes related names and rates in the detail payload for the datatable', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $unit = Unit::factory()->create(['client_id' => $client->id, 'name' => 'Karton']);
    $group = Group::factory()->create(['client_id' => $client->id, 'name' => 'Group A']);
    $costCenter = CostCenter::factory()->create(['client_id' => $client->id, 'name' => 'CC Produksi']);
    Product::create([
        'client_id' => $client->id,
        'sku' => 'SKU-001',
        'name' => 'Kopi Sachet 25g',
        'unit_id' => $unit->id,
        'group_id' => $group->id,
        'cost_center_id' => $costCenter->id,
        'po_price' => 15000.500,
        'old_employee_rate' => 500.250,
        'new_employee_rate' => 550.750,
        'estimated_output_per_hour' => 120,
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('products.index', ['draw' => 1, 'start' => 0, 'length' => 10]));

    $response->assertOk();
    $html = $response->json('data.0.action');

    expect($html)
        ->toContain('data-product-detail=')
        ->toContain('&quot;unit_name&quot;:&quot;Karton&quot;')
        ->toContain('&quot;group_name&quot;:&quot;Group A&quot;')
        ->toContain('&quot;cost_center_name&quot;:&quot;CC Produksi&quot;')
        ->toContain('&quot;po_price&quot;:&quot;15000.500&quot;');
});
