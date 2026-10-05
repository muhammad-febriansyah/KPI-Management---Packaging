<?php

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders audit date filters as date pickers', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('audit.index'));

    $response->assertOk()
        ->assertSee('data-audit-date-from data-datepicker type="text"', false)
        ->assertSee('data-audit-date-to data-datepicker type="text"', false)
        ->assertSee('href="'.route('audit.index').'"', false)
        ->assertSee('Semua aktivitas')
        ->assertSee('Data ditambahkan')
        ->assertSee('Aktivitas')
        ->assertDontSee('data-audit-date-from type="date"', false)
        ->assertDontSee('data-audit-date-to type="date"', false);
});

it('describes audit activities in plain Indonesian', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    AuditLog::query()->create([
        'client_id' => $client->getKey(),
        'user_id' => $user->getKey(),
        'action' => 'login',
        'auditable_type' => User::class,
        'auditable_id' => $user->getKey(),
        'ip_address' => '127.0.0.1',
    ]);
    AuditLog::query()->create([
        'client_id' => $client->getKey(),
        'user_id' => $user->getKey(),
        'action' => 'update',
        'auditable_type' => 'App\\Models\\WorkRealization',
        'auditable_id' => 10,
        'ip_address' => '127.0.0.1',
    ]);
    AuditLog::query()->create([
        'client_id' => $client->getKey(),
        'user_id' => $user->getKey(),
        'action' => 'create',
        'auditable_type' => 'App\\Models\\Product',
        'auditable_id' => 11,
        'ip_address' => '127.0.0.1',
    ]);

    $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('audit.index', ['draw' => 1, 'start' => 0, 'length' => 10]))
        ->assertOk()
        ->assertJsonFragment(['description' => 'Masuk ke sistem'])
        ->assertJsonFragment(['description' => 'Mengubah realisasi pekerjaan'])
        ->assertJsonFragment(['description' => 'Menambahkan produk']);
});

it('hides audit log navigation from non-super-admin users', function () {
    $client = Client::factory()->create();
    $role = Role::query()->create(['code' => 'dashboard-only', 'name' => 'Dashboard Only']);
    $permission = Permission::query()->firstOrCreate(['code' => 'menu.dashboard'], ['name' => 'Menu: Dashboard']);
    $role->permissions()->attach($permission);
    $user = User::factory()->create();
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);

    $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('href="'.route('audit.index').'"', false);
});
