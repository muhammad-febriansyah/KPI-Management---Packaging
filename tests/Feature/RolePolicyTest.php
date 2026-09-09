<?php

use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets an active super admin view the role access list', function () {
    $user = User::factory()->superAdmin()->create();

    expect((new RolePolicy)->viewAny($user))->toBeTrue();
});

it('forbids an inactive super admin from viewing the role access list', function () {
    $user = User::factory()->superAdmin()->inactive()->create();

    expect((new RolePolicy)->viewAny($user))->toBeFalse();
});

it('forbids a regular user from viewing the role access list', function () {
    $user = User::factory()->create();

    expect((new RolePolicy)->viewAny($user))->toBeFalse();
});

it('lets an active super admin update a non super-admin role', function () {
    $user = User::factory()->superAdmin()->create();
    $role = Role::factory()->create(['code' => 'client']);

    expect((new RolePolicy)->update($user, $role))->toBeTrue();
});

it('forbids updating the super-admin role itself, even by a super admin', function () {
    $user = User::factory()->superAdmin()->create();
    $role = Role::factory()->create(['code' => 'super-admin']);

    expect((new RolePolicy)->update($user, $role))->toBeFalse();
});

it('forbids a regular user from updating a role', function () {
    $user = User::factory()->create();
    $role = Role::factory()->create(['code' => 'client']);

    expect((new RolePolicy)->update($user, $role))->toBeFalse();
});
