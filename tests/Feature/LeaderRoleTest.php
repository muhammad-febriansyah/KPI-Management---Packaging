<?php

use App\Models\Client;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('gives leader realization access while employee only gets own payroll', function () {
    $client = Client::factory()->create();
    $leader = User::factory()->create();
    $employee = User::factory()->create();
    $leaderRole = Role::query()->where('code', 'leader')->firstOrFail();
    $employeeRole = Role::query()->firstOrCreate(['code' => 'employee'], ['name' => 'Karyawan']);
    $employeeRole->permissions()->syncWithoutDetaching([
        Permission::query()->firstOrCreate(['code' => 'menu.my-payroll'], ['name' => 'Menu: Gaji Saya'])->getKey(),
    ]);

    $leader->clients()->attach($client, ['role_id' => $leaderRole->getKey(), 'is_default' => true, 'status' => 'active']);
    $employee->clients()->attach($client, ['role_id' => $employeeRole->getKey(), 'is_default' => true, 'status' => 'active']);

    $this->actingAs($leader)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('realizations.index'))
        ->assertOk()
        ->assertSee('Gaji Saya')
        ->assertSee('Realisasi');

    $this->actingAs($employee)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('realizations.index'))
        ->assertForbidden();

    $this->actingAs($employee)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('reports.payroll'))
        ->assertOk()
        ->assertSee('Gaji Saya');
});
