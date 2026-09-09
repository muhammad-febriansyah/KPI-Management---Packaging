<?php

use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use App\Policies\ClientPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows an active user to select an assigned active client', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();
    $role = Role::factory()->create();
    $user->clients()->attach($client, [
        'role_id' => $role->getKey(),
        'status' => 'active',
    ]);

    $allowed = app(ClientPolicy::class)->select($user, $client);

    expect($allowed)->toBeTrue();
});

it('forbids selecting a client without an active assignment', function (string $pivotStatus) {
    $user = User::factory()->create();
    $client = Client::factory()->create();

    if ($pivotStatus !== 'missing') {
        $role = Role::factory()->create();
        $user->clients()->attach($client, [
            'role_id' => $role->getKey(),
            'status' => $pivotStatus,
        ]);
    }

    $allowed = app(ClientPolicy::class)->select($user, $client);

    expect($allowed)->toBeFalse();
})->with([
    'missing assignment' => 'missing',
    'inactive assignment' => 'inactive',
]);

it('forbids an inactive user from selecting an assigned client', function () {
    $user = User::factory()->inactive()->create();
    $client = Client::factory()->create();
    $role = Role::factory()->create();
    $user->clients()->attach($client, [
        'role_id' => $role->getKey(),
        'status' => 'active',
    ]);

    $allowed = app(ClientPolicy::class)->select($user, $client);

    expect($allowed)->toBeFalse();
});

it('allows an active super admin to select an active client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $allowed = app(ClientPolicy::class)->select($user, $client);

    expect($allowed)->toBeTrue();
});

it('forbids selecting an inactive client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->inactive()->create();

    $allowed = app(ClientPolicy::class)->select($user, $client);

    expect($allowed)->toBeFalse();
});
