<?php

use App\Models\Client;
use App\Models\CostCenter;
use App\Models\Employee;
use App\Models\Group;
use App\Models\Product;
use App\Models\Role;
use App\Models\Scopes\ClientScope;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use App\Policies\CostCenterPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\GroupPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ShiftPolicy;
use App\Policies\UnitPolicy;
use App\Services\CurrentClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A user holding an active assignment to every given client.
 */
function memberOfClients(Client ...$clients): User
{
    $user = User::factory()->create();
    $role = Role::factory()->create();

    foreach ($clients as $client) {
        $user->clients()->attach($client, ['role_id' => $role->getKey(), 'status' => 'active']);
    }

    return $user;
}

dataset('tenant resources', [
    'employee' => [Employee::class, EmployeePolicy::class, 'employees.destroy'],
    'product' => [Product::class, ProductPolicy::class, 'products.destroy'],
    'unit' => [Unit::class, UnitPolicy::class, 'units.destroy'],
    'group' => [Group::class, GroupPolicy::class, 'groups.destroy'],
    'shift' => [Shift::class, ShiftPolicy::class, 'shifts.destroy'],
    'cost center' => [CostCenter::class, CostCenterPolicy::class, 'cost-centers.destroy'],
]);

it('allows updating a record owned by the active client', function (string $model, string $policy, string $route) {
    $activeClient = Client::factory()->create();
    $user = memberOfClients($activeClient);
    $record = $model::factory()->create(['client_id' => $activeClient->getKey()]);
    app(CurrentClientService::class)->set($activeClient);

    $allowed = app($policy)->update($user, $record);

    expect($allowed)->toBeTrue();
})->with('tenant resources');

it('forbids updating a record owned by another client the user is also assigned to', function (string $model, string $policy, string $route) {
    $activeClient = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $user = memberOfClients($activeClient, $otherClient);
    $record = $model::factory()->create(['client_id' => $otherClient->getKey()]);
    app(CurrentClientService::class)->set($activeClient);

    $allowed = app($policy)->update($user, $record);

    expect($allowed)->toBeFalse();
})->with('tenant resources');

it('forbids a super admin from updating a record outside the active client', function (string $model, string $policy, string $route) {
    $activeClient = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $user = User::factory()->superAdmin()->create();
    $record = $model::factory()->create(['client_id' => $otherClient->getKey()]);
    app(CurrentClientService::class)->set($activeClient);

    $allowed = app($policy)->update($user, $record);

    expect($allowed)->toBeFalse();
})->with('tenant resources');

it('forbids updating a record when no client is active', function (string $model, string $policy, string $route) {
    $client = Client::factory()->create();
    $user = memberOfClients($client);
    $record = $model::factory()->create(['client_id' => $client->getKey()]);

    $allowed = app($policy)->update($user, $record);

    expect($allowed)->toBeFalse();
})->with('tenant resources');

it('returns 404 and keeps the record when deleting one owned by another client', function (string $model, string $policy, string $route) {
    $activeClient = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $user = memberOfClients($activeClient, $otherClient);
    $record = $model::factory()->create(['client_id' => $otherClient->getKey()]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $activeClient->getKey()])
        ->deleteJson(route($route, $record));

    $response->assertNotFound();
    // Bypass only the tenant scope, so a soft delete would still read back as gone.
    expect($model::withoutGlobalScope(ClientScope::class)->find($record->getKey()))->not->toBeNull();
})->with('tenant resources');

it('forbids viewing a product owned by another client', function () {
    $activeClient = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $user = memberOfClients($activeClient, $otherClient);
    $product = Product::factory()->create(['client_id' => $otherClient->getKey()]);
    app(CurrentClientService::class)->set($activeClient);

    $allowed = app(ProductPolicy::class)->view($user, $product);

    expect($allowed)->toBeFalse();
});

it('allows a member of the active client to create a product', function () {
    $activeClient = Client::factory()->create();
    $user = memberOfClients($activeClient);
    app(CurrentClientService::class)->set($activeClient);

    $allowed = app(ProductPolicy::class)->create($user);

    expect($allowed)->toBeTrue();
});
