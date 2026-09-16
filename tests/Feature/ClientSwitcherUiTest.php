<?php

use App\Models\Client;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A role that may open the product pages, which is where these tests read the header from.
 */
function switcherTestRole(): Role
{
    $role = Role::factory()->create();
    $role->permissions()->attach(Permission::query()->firstOrCreate(['code' => 'menu.products'], ['name' => 'Menu: Produk']));

    return $role;
}

it('offers every accessible client in the header switcher', function () {
    $user = User::factory()->superAdmin()->create();
    $active = Client::factory()->create(['name' => 'PT Satu']);
    Client::factory()->create(['name' => 'PT Dua']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $active->getKey()])
        ->get(route('products.index'));

    $response->assertOk()
        ->assertSee('data-client-switcher', false)
        ->assertSee('PT Satu')
        ->assertSee('PT Dua');
});

it('hides the switcher when the user reaches only one client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();
    $user->clients()->attach($client, ['role_id' => switcherTestRole()->getKey(), 'status' => 'active']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('products.index'));

    $response->assertOk()->assertDontSee('data-client-switcher', false);
});

it('does not offer a client the user has no access to', function () {
    $user = User::factory()->create();
    $reachable = Client::factory()->create(['name' => 'PT Terjangkau']);
    $otherClient = Client::factory()->create(['name' => 'PT Terlarang']);
    $user->clients()->attach($reachable, ['role_id' => switcherTestRole()->getKey(), 'status' => 'active']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $reachable->getKey()])
        ->get(route('products.index'));

    $response->assertOk()->assertDontSee($otherClient->name);
});
