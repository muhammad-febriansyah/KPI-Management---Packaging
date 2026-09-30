<?php

use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests to the login page', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirectToRoute('login');
});

it('renders the dashboard with the active client context', function () {
    $user = User::factory()->create(['name' => 'Rani Kusuma']);
    $client = Client::factory()->create(['name' => 'PT Sinar Maju Sejahtera']);
    $role = Role::factory()->create();
    $user->clients()->attach($client, [
        'role_id' => $role->getKey(),
        'is_default' => true,
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertSee('Ringkasan performa')
        ->assertSee('Tren output bulanan')
        ->assertSee('data-dashboard-output-chart', false)
        ->assertSee('Output per shift bulan ini')
        ->assertSee('data-dashboard-shift-chart', false)
        ->assertDontSee('30 Hari Terakhir')
        ->assertDontSee('Total komplain')
        ->assertDontSee('data-filter-toggle')
        ->assertDontSee('Unduh laporan')
        ->assertViewHas('currentClient', fn (Client $currentClient): bool => $currentClient->is($client));
    expect(session('current_client_id'))->toBe($client->getKey());
});

it('renders the dashboard for an authenticated user without a client', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()->assertViewHas('currentClient', null);
});

it('gives an employee-role user their own scoped dashboard', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();
    $employeeRole = Role::query()->firstOrCreate(['code' => 'employee'], ['name' => 'Karyawan']);
    $user->clients()->attach($client, ['role_id' => $employeeRole->getKey(), 'is_default' => true, 'status' => 'active']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertViewIs('dashboard-employee')
        ->assertSee('Ringkasan gaji saya')
        ->assertSee('Profil saya')
        ->assertSee('Belum ada data gaji bulan ini')
        ->assertSee('data-dashboard-output-chart', false)
        ->assertDontSee('Akses pribadi')
        ->assertDontSee('Realisasi terbaru saya')
        ->assertDontSee('data-global-search', false)
        ->assertDontSee('Pencarian global')
        ->assertDontSee('data-realization-fill-modal', false)
        ->assertDontSee('Isi sekarang');
});

it('gives a leader an operational dashboard separate from employee dashboard', function () {
    $user = User::factory()->create();
    $client = Client::factory()->create();
    $leaderRole = Role::query()->firstOrCreate(['code' => 'leader'], ['name' => 'Leader']);
    $user->clients()->attach($client, ['role_id' => $leaderRole->getKey(), 'is_default' => true, 'status' => 'active']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertViewIs('dashboard-leader')
        ->assertSee('Ringkasan operasional')
        ->assertSee('Realisasi terbaru saya')
        ->assertSee('Output per shift')
        ->assertSee('Gaji saya bulan ini')
        ->assertDontSee('Ringkasan gaji saya')
        ->assertViewHas('metrics.unassignedToday', 0);
});
