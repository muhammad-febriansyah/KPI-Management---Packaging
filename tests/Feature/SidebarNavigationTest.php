<?php

use App\Models\Client;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('only shows menus granted to the user\'s role in the sidebar', function () {
    $client = Client::factory()->create();
    $role = Role::query()->create(['code' => 'client', 'name' => 'Client']);
    $dashboard = Permission::query()->create(['code' => 'menu.dashboard', 'name' => 'Menu: Dashboard']);
    $role->permissions()->attach($dashboard);

    $user = User::factory()->create();
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee(route('dashboard'), false)
        ->assertDontSee(route('products.index'), false);
});

it('reflects a menu revoked by the super admin without redeploying', function () {
    $client = Client::factory()->create();
    $role = Role::query()->create(['code' => 'client', 'name' => 'Client']);
    $dashboard = Permission::query()->create(['code' => 'menu.dashboard', 'name' => 'Menu: Dashboard']);
    $products = Permission::query()->create(['code' => 'menu.products', 'name' => 'Menu: Produk']);
    $role->permissions()->attach([$dashboard->id, $products->id]);

    $user = User::factory()->create();
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);

    $this->actingAs($user)->get(route('dashboard'))->assertSee(route('products.index'), false);

    $role->permissions()->detach($products);

    $this->actingAs($user)->get(route('dashboard'))->assertDontSee(route('products.index'), false);
});

it('shows master client under the setting menu for super admins', function () {
    $admin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee(route('clients.index'), false)
        ->assertSee(route('units.index'), false)
        ->assertSee(route('groups.index'), false)
        ->assertSee(route('cost-centers.index'), false)
        ->assertSee(asset('images/logo-white.png'), false)
        ->assertSee(asset('favicon.ico'), false);

    $content = $response->getContent();

    expect(strpos($content, route('units.index')))
        ->toBeLessThan(strpos($content, route('products.index')))
        ->and(strpos($content, route('groups.index')))
        ->toBeLessThan(strpos($content, route('products.index')))
        ->and(strpos($content, route('cost-centers.index')))
        ->toBeLessThan(strpos($content, route('products.index')));
});
