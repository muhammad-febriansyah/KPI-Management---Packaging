<?php

use App\Models\Batch;
use App\Models\Client;
use App\Models\CostCenter;
use App\Models\DeductionPeriod;
use App\Models\Employee;
use App\Models\EmployeeDeduction;
use App\Models\Group;
use App\Models\Product;
use App\Models\RealizationEmployee;
use App\Models\Scopes\ClientScope;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\WorkRealization;
use App\Services\CurrentClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers the client scope on every model owned by a client', function (string $model) {
    $scopes = array_keys((new $model)->getGlobalScopes());

    expect($scopes)->toContain(ClientScope::class);
})->with([
    Batch::class,
    CostCenter::class,
    DeductionPeriod::class,
    Employee::class,
    EmployeeDeduction::class,
    Group::class,
    Product::class,
    RealizationEmployee::class,
    Shift::class,
    Unit::class,
    WorkRealization::class,
]);

it('excludes records of other clients while a client is active', function () {
    $activeClient = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $own = Employee::factory()->create(['client_id' => $activeClient->getKey()]);
    Employee::factory()->create(['client_id' => $otherClient->getKey()]);
    app(CurrentClientService::class)->set($activeClient);

    $found = Employee::query()->pluck('id');

    expect($found->all())->toBe([$own->getKey()]);
});

it('does not find a record of another client by its id', function () {
    $activeClient = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $foreign = Employee::factory()->create(['client_id' => $otherClient->getKey()]);
    app(CurrentClientService::class)->set($activeClient);

    $found = Employee::query()->find($foreign->getKey());

    expect($found)->toBeNull();
});

it('scopes a query that joins another table on a qualified client_id', function () {
    $activeClient = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $group = Group::factory()->create(['client_id' => $activeClient->getKey()]);
    $own = Employee::factory()->create(['client_id' => $activeClient->getKey(), 'group_id' => $group->getKey()]);
    Employee::factory()->create(['client_id' => $otherClient->getKey()]);
    app(CurrentClientService::class)->set($activeClient);

    $found = Employee::query()->join('groups', 'groups.id', '=', 'employees.group_id')->pluck('employees.id');

    expect($found->all())->toBe([$own->getKey()]);
});

it('stamps a new record with the active client when none is given', function () {
    $activeClient = Client::factory()->create();
    app(CurrentClientService::class)->set($activeClient);

    $unit = Unit::query()->create(['code' => 'UNIT-1', 'name' => 'Karton', 'status' => 'active']);

    expect($unit->client_id)->toBe($activeClient->getKey());
});

it('rejects a new record stamped with a client other than the active one', function () {
    $activeClient = Client::factory()->create();
    $otherClient = Client::factory()->create();
    app(CurrentClientService::class)->set($activeClient);

    $write = fn () => Unit::query()->create(['client_id' => $otherClient->getKey(), 'code' => 'UNIT-1', 'name' => 'Karton', 'status' => 'active']);

    expect($write)->toThrow(RuntimeException::class);
    $this->assertDatabaseEmpty('units');
});

it('accepts a new record stamped with the active client', function () {
    $activeClient = Client::factory()->create();
    app(CurrentClientService::class)->set($activeClient);

    $unit = Unit::query()->create(['client_id' => $activeClient->getKey(), 'code' => 'UNIT-1', 'name' => 'Karton', 'status' => 'active']);

    expect($unit->client_id)->toBe($activeClient->getKey());
});

it('accepts a numeric string client id from browser form submissions', function () {
    $activeClient = Client::factory()->create();
    app(CurrentClientService::class)->set($activeClient);

    $unit = Unit::query()->create(['client_id' => (string) $activeClient->getKey(), 'code' => 'UNIT-1', 'name' => 'Karton', 'status' => 'active']);

    expect((int) $unit->client_id)->toBe($activeClient->getKey());
});

it('keeps an explicit client_id when no client is active', function () {
    $client = Client::factory()->create();

    $unit = Unit::query()->create(['client_id' => $client->getKey(), 'code' => 'UNIT-1', 'name' => 'Karton', 'status' => 'active']);

    expect($unit->client_id)->toBe($client->getKey());
});

it('queries across every client when no client is active', function () {
    $firstClient = Client::factory()->create();
    $secondClient = Client::factory()->create();
    Employee::factory()->create(['client_id' => $firstClient->getKey()]);
    Employee::factory()->create(['client_id' => $secondClient->getKey()]);

    $found = Employee::query()->count();

    expect($found)->toBe(2);
});
