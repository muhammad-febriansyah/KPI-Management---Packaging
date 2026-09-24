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
        ->assertSee('min-w-[840px]', false)
        ->assertSee('flex-col', false)
        ->assertSee('w-full', false)
        ->assertSee('data-product-create', false)
        ->assertSee('name="employee_rate"', false)
        ->assertDontSee('name="old_employee_rate"', false)
        ->assertDontSee('name="new_employee_rate"', false)
        ->assertDontSee('data-manage-modal-open="unit"', false)
        ->assertDontSee('data-manage-modal-open="group"', false)
        ->assertDontSee('data-manage-modal-open="cost-center"', false);
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
        'employee_rate' => 550.750,
        'estimated_output_per_hour' => 120,
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('products.index', ['draw' => 1, 'start' => 0, 'length' => 10]));

    $response->assertOk();
    $response->assertJsonPath('data.0.client_name', $client->name);
    $html = $response->json('data.0.action');

    expect($html)
        ->toContain('data-product-detail=')
        ->toContain('&quot;client_code&quot;:&quot;'.$client->code.'&quot;')
        ->toContain('&quot;client_name&quot;:&quot;'.$client->name.'&quot;')
        ->toContain('&quot;client_status&quot;:&quot;active&quot;')
        ->toContain('&quot;unit_name&quot;:&quot;Karton&quot;')
        ->toContain('&quot;group_name&quot;:&quot;Group A&quot;')
        ->toContain('&quot;cost_center_name&quot;:&quot;CC Produksi&quot;')
        ->toContain('&quot;po_price&quot;:&quot;15000.500&quot;')
        ->toContain('&quot;employee_rate&quot;:&quot;550.750&quot;');
});

it('shows a searchable client selector in the product form', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create(['name' => 'PT Produk Aktif']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('products.index'));

    $response->assertOk()
        ->assertSee('>Client<', false)
        ->assertSee('name="client_id"', false)
        ->assertSee('data-select2-select', false)
        ->assertSee('data-select2-remote="'.route('clients.options').'"', false)
        ->assertSee($client->name, false);
});

it('loads only active units remotely for the product form', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $inactiveUnit = Unit::factory()->create(['client_id' => $client->getKey(), 'name' => 'Lusin', 'status' => 'inactive']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('products.index'));

    $response->assertOk()
        ->assertSee('data-tom-select-remote="'.route('units.options').'"', false)
        ->assertDontSee('value="'.$inactiveUnit->getKey().'"', false)
        ->assertDontSee('>'.$inactiveUnit->name.'<', false);
});

it('rejects an inactive unit when storing a product', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $inactiveUnit = Unit::factory()->create(['client_id' => $client->getKey(), 'status' => 'inactive']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('products.store'), [
            'client_id' => $client->getKey(),
            'unit_id' => $inactiveUnit->getKey(),
            'sku' => 'SKU-INACTIVE-UNIT',
            'name' => 'Produk Unit Nonaktif',
            'status' => 'active',
        ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('unit_id');
    $this->assertDatabaseMissing('products', ['sku' => 'SKU-INACTIVE-UNIT']);
});

it('creates a product for the selected client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('products.store'), [
            'client_id' => $otherClient->getKey(),
            'sku' => 'SKU-CREATE-001',
            'name' => 'Produk Baru',
            'po_price' => 12500,
            'employee_rate' => 300,
            'estimated_output_per_hour' => 120,
            'status' => 'active',
        ]);

    $response->assertCreated()->assertJsonPath('message', 'Produk berhasil ditambahkan.');
    $this->assertDatabaseHas('products', [
        'client_id' => $otherClient->getKey(),
        'sku' => 'SKU-CREATE-001',
        'name' => 'Produk Baru',
        'employee_rate' => 300,
    ]);
});

it('updates a product without changing its client ownership', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $product = Product::factory()->create([
        'client_id' => $client->getKey(),
        'sku' => 'SKU-UPDATE-001',
        'name' => 'Nama Lama',
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('products.update', $product), [
            'client_id' => $otherClient->getKey(),
            'sku' => 'SKU-UPDATE-001-REV',
            'name' => 'Nama Baru',
            'status' => 'inactive',
        ]);

    $response->assertOk()->assertJsonPath('message', 'Produk berhasil diperbarui.');
    $this->assertDatabaseHas('products', [
        'id' => $product->getKey(),
        'client_id' => $otherClient->getKey(),
        'sku' => 'SKU-UPDATE-001-REV',
        'name' => 'Nama Baru',
        'status' => 'inactive',
    ]);
});
