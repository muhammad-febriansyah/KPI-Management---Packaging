<?php

use App\Models\Batch;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Group;
use App\Models\Product;
use App\Models\RealizationEmployee;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use App\Models\WorkRealization;
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
        ->assertSee('Realisasi terbaru area')
        ->assertSee('Output per shift')
        ->assertSee('Gaji saya bulan ini')
        ->assertDontSee('Ringkasan gaji saya')
        ->assertViewHas('metrics.unassignedToday', 0);
});

it('limits leader dashboard metrics and charts to the leader group', function () {
    $user = User::factory()->create();
    $creator = User::factory()->create();
    $client = Client::factory()->create();
    $leaderGroup = Group::factory()->recycle($client)->create(['name' => 'Area A']);
    $otherGroup = Group::factory()->recycle($client)->create(['name' => 'Area B']);
    $leaderRole = Role::query()->firstOrCreate(['code' => 'leader'], ['name' => 'Leader']);
    $user->clients()->attach($client, ['role_id' => $leaderRole->getKey(), 'is_default' => true, 'status' => 'active']);
    Employee::factory()->recycle($client)->create(['user_id' => $user->getKey(), 'group_id' => $leaderGroup->getKey()]);
    $areaEmployee = Employee::factory()->recycle($client)->create(['group_id' => $leaderGroup->getKey()]);
    $otherEmployee = Employee::factory()->recycle($client)->create(['group_id' => $otherGroup->getKey()]);
    $unit = Unit::factory()->recycle($client)->create();
    $areaProduct = Product::factory()->recycle($client)->create(['unit_id' => $unit->getKey(), 'group_id' => $leaderGroup->getKey()]);
    $otherProduct = Product::factory()->recycle($client)->create(['unit_id' => $unit->getKey(), 'group_id' => $otherGroup->getKey()]);
    $shift = Shift::factory()->recycle($client)->create();
    $areaBatch = Batch::query()->create(['client_id' => $client->getKey(), 'batch_no' => 'AREA-A-BATCH', 'product_id' => $areaProduct->getKey(), 'status' => 'active']);
    Batch::query()->create(['client_id' => $client->getKey(), 'batch_no' => 'AREA-B-BATCH', 'product_id' => $otherProduct->getKey(), 'status' => 'active']);

    $createRealization = fn (Product $product, Batch $batch, string $productName, float $output): WorkRealization => WorkRealization::query()->create([
        'client_id' => $client->getKey(),
        'work_date' => today()->toDateString(),
        'shift_id' => $shift->getKey(),
        'batch_id' => $batch->getKey(),
        'product_id' => $product->getKey(),
        'sku_snapshot' => $product->sku,
        'product_name_snapshot' => $productName,
        'unit_name_snapshot' => 'Karton',
        'total_output' => $output,
        'start_time' => '07:00:00',
        'end_time' => '08:00:00',
        'created_by' => $creator->getKey(),
    ]);
    $assignEmployee = fn (WorkRealization $realization, Employee $employee, float $output) => RealizationEmployee::query()->create([
        'client_id' => $client->getKey(),
        'work_realization_id' => $realization->getKey(),
        'employee_id' => $employee->getKey(),
        'rate_category_snapshot' => 'baru',
        'rate_per_unit_snapshot' => 0,
        'allocation_output' => $output,
        'gross_amount' => 0,
    ]);

    $areaRealization = $createRealization($areaProduct, $areaBatch, 'Output Area A', 12.0);
    $assignEmployee($areaRealization, $areaEmployee, 12.0);
    $otherRealization = $createRealization($otherProduct, Batch::query()->where('batch_no', 'AREA-B-BATCH')->firstOrFail(), 'SECRET AREA B', 99.0);
    $assignEmployee($otherRealization, $otherEmployee, 99.0);
    $mixedRealization = $createRealization($areaProduct, $areaBatch, 'MIXED AREA OUTPUT', 500.0);
    $assignEmployee($mixedRealization, $areaEmployee, 250.0);
    $assignEmployee($mixedRealization, $otherEmployee, 250.0);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertSee('Lingkup data: Area A')
        ->assertDontSee('SECRET AREA B')
        ->assertDontSee('MIXED AREA OUTPUT')
        ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['realizationsToday'] === 1
            && (float) $metrics['outputToday'] === 12.0
            && $metrics['activeBatches'] === 1
            && $metrics['assignedEmployeesToday'] === 1)
        ->assertViewHas('recentRealizations', fn ($realizations): bool => $realizations->modelKeys() === [$areaRealization->getKey()])
        ->assertViewHas('outputTrend', fn (array $trend): bool => array_sum(array_column($trend, 'value')) === 12.0)
        ->assertViewHas('shiftSummaries', fn ($summaries): bool => (float) $summaries->first()->total_output === 12.0);
});
