<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use App\Models\WorkRealization;
use App\Notifications\RealizationAssigned;
use App\Notifications\RealizationSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function notifiableEmployeeUser(Client $client): array
{
    $role = Role::query()->firstOrCreate(['code' => 'employee'], ['name' => 'Karyawan']);
    $permission = Permission::query()->firstOrCreate(['code' => 'menu.realizations'], ['name' => 'Menu: Realisasi']);
    $role->permissions()->syncWithoutDetaching([$permission->getKey()]);
    $employeeUser = User::factory()->create();
    $employeeUser->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'user_id' => $employeeUser->getKey()]);

    return [$employeeUser, $employee];
}

it('notifies the employee when assigned to a realization via store', function () {
    Notification::fake();
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    [$employeeUser, $employee] = notifiableEmployeeUser($client);

    $this->actingAs($admin)->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), ['employee_ids' => [$employee->getKey()]]);

    Notification::assertSentTo($employeeUser, RealizationAssigned::class);
});

it('notifies the employee when assigned to an existing realization via the assign endpoint', function () {
    Notification::fake();
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    [$employeeUser, $employee] = notifiableEmployeeUser($client);

    $storeResponse = $this->actingAs($admin)->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), []);
    $realizationId = $storeResponse->json('id') ?? DB::table('work_realizations')->where('client_id', $client->getKey())->value('id');

    $this->actingAs($admin)->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.assign', $realizationId), ['employee_ids' => [$employee->getKey()]]);

    Notification::assertSentTo($employeeUser, RealizationAssigned::class);
});

it('does not re-notify an employee already assigned when assign is called again', function () {
    Notification::fake();
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    [$employeeUser, $employee] = notifiableEmployeeUser($client);

    $storeResponse = $this->actingAs($admin)->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('realizations.store'), []);
    $realizationId = DB::table('work_realizations')->where('client_id', $client->getKey())->value('id');

    $this->actingAs($admin)->withSession(['current_client_id' => $client->getKey()])->postJson(route('realizations.assign', $realizationId), ['employee_ids' => [$employee->getKey()]]);
    $this->actingAs($admin)->withSession(['current_client_id' => $client->getKey()])->postJson(route('realizations.assign', $realizationId), ['employee_ids' => [$employee->getKey()]]);

    Notification::assertSentToTimes($employeeUser, RealizationAssigned::class, 1);
});

it('notifies super admins when an employee submits their work result', function () {
    Notification::fake();
    $admin = User::factory()->superAdmin()->create();
    $otherAdmin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    [$employeeUser, $employee] = notifiableEmployeeUser($client);
    $shift = Shift::factory()->create(['client_id' => $client->getKey()]);
    $unit = Unit::factory()->create(['client_id' => $client->getKey()]);
    $productId = DB::table('products')->insertGetId([
        'client_id' => $client->getKey(), 'sku' => 'SKU-N1', 'name' => 'Produk', 'unit_id' => $unit->getKey(),
        'po_price' => 0, 'employee_rate' => 0, 'status' => 'active',
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $realizationId = DB::table('work_realizations')->insertGetId([
        'client_id' => $client->getKey(), 'work_date' => now()->toDateString(), 'shift_id' => $shift->getKey(), 'product_id' => $productId,
        'sku_snapshot' => 'SKU-N1', 'product_name_snapshot' => 'Produk', 'unit_name_snapshot' => 'PCS', 'total_output' => 10,
        'start_time' => '08:00', 'end_time' => '16:00', 'created_by' => $admin->getKey(),
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('realization_employees')->insert([
        'client_id' => $client->getKey(), 'work_realization_id' => $realizationId, 'employee_id' => $employee->getKey(),
        'rate_category_snapshot' => $employee->rate_category, 'rate_per_unit_snapshot' => 0, 'allocation_output' => null,
        'gross_amount' => 0, 'created_at' => now(),
    ]);

    $this->actingAs($employeeUser)->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('realizations.update', $realizationId), ['total_output' => 25, 'start_time' => '08:30', 'end_time' => '16:30']);

    Notification::assertSentTo($admin, RealizationSubmitted::class);
    Notification::assertSentTo($otherAdmin, RealizationSubmitted::class);
    Notification::assertNotSentTo($employeeUser, RealizationSubmitted::class);
});

it('lets a user mark their own notification as read but not another user\'s', function () {
    $client = Client::factory()->create();
    [$employeeUser, $employee] = notifiableEmployeeUser($client);
    $otherUser = User::factory()->create();

    $realization = WorkRealization::query()->create(['client_id' => $client->getKey(), 'created_by' => $otherUser->getKey()]);
    $employeeUser->notify(new RealizationAssigned($realization));
    $notificationId = $employeeUser->notifications()->first()->id;

    $this->actingAs($otherUser)->postJson(route('notifications.read', $notificationId));
    expect($employeeUser->fresh()->unreadNotifications)->toHaveCount(1);

    $this->actingAs($employeeUser)->postJson(route('notifications.read', $notificationId));
    expect($employeeUser->fresh()->unreadNotifications)->toHaveCount(0);
});
