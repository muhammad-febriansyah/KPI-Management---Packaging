<?php

use App\Models\Client;
use App\Models\CostCenter;
use App\Models\Group;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deletes a product that nothing references', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $product = Product::factory()->create(['client_id' => $client->getKey()]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->deleteJson(route('products.destroy', $product));

    $response->assertOk()->assertJsonPath('message', 'Produk berhasil dihapus.');
    $this->assertDatabaseMissing('products', ['id' => $product->getKey()]);
});

it('returns 409 and keeps a master record that a product still references', function (string $route, string $foreignKey, string $expectedMessage) {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $master = match ($route) {
        'units.destroy' => Unit::factory()->create(['client_id' => $client->getKey()]),
        'groups.destroy' => Group::factory()->create(['client_id' => $client->getKey()]),
        'cost-centers.destroy' => CostCenter::factory()->create(['client_id' => $client->getKey()]),
    };
    Product::factory()->create(['client_id' => $client->getKey(), $foreignKey => $master->getKey()]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->deleteJson(route($route, $master));

    $response->assertConflict()->assertJsonPath('message', $expectedMessage);
    $this->assertDatabaseHas($master->getTable(), ['id' => $master->getKey()]);
})->with([
    'unit' => ['units.destroy', 'unit_id', 'Satuan tidak dapat dihapus karena masih dipakai pada produk.'],
    'group' => ['groups.destroy', 'group_id', 'Group tidak dapat dihapus karena masih dipakai pada produk atau karyawan.'],
    'cost center' => ['cost-centers.destroy', 'cost_center_id', 'Cost center tidak dapat dihapus karena masih dipakai pada produk.'],
]);

it('deletes a master record that no product references', function (string $route, string $model) {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $master = $model::factory()->create(['client_id' => $client->getKey()]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->deleteJson(route($route, $master));

    $response->assertOk();
    $this->assertDatabaseMissing($master->getTable(), ['id' => $master->getKey()]);
})->with([
    'unit' => ['units.destroy', Unit::class],
    'group' => ['groups.destroy', Group::class],
    'cost center' => ['cost-centers.destroy', CostCenter::class],
]);
