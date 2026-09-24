<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function validEmployeePayload(array $overrides = []): array
{
    return array_merge([
        'sim_id' => 'PEG1234',
        'full_name' => 'Ananda Julian',
        'email' => 'ananda@example.com',
        'phone' => '081234567890',
        'join_date' => '2026-01-01',
        'gender' => 'male',
        'employee_status' => 'permanent',
        'marital_status' => 'married',
    ], $overrides);
}

it('stores an employee with sim id, email, and marital status', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('employees.store'), validEmployeePayload());

    $response->assertCreated();
    $employee = Employee::query()->where('client_id', $client->getKey())->firstOrFail();
    expect($employee->employee_no)->toMatch('/^\d{9}$/');
    $this->assertDatabaseHas('employees', [
        'client_id' => $client->getKey(),
        'id' => $employee->getKey(),
        'sim_id' => 'PEG1234',
        'email' => 'ananda@example.com',
        'marital_status' => 'married',
    ]);
    $this->assertDatabaseHas('users', ['id' => $employee->user_id, 'name' => 'Ananda Julian', 'username' => $employee->employee_no, 'email' => 'ananda@example.com', 'status' => 'active']);
    $this->assertDatabaseHas('client_user', ['client_id' => $client->getKey(), 'user_id' => $employee->user_id, 'status' => 'active']);
    expect(Hash::check('password', User::query()->findOrFail($employee->user_id)->password))->toBeTrue();
});

it('keeps email and password out of the employee master form', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('employees.index'));

    $response->assertOk()
        ->assertDontSee('name="email"', false)
        ->assertDontSee('name="password"', false)
        ->assertSee('Password dapat diatur dari menu Akses.');
});

it('requires marital status when storing an employee', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('employees.store'), validEmployeePayload(['marital_status' => '']));

    $response->assertInvalid(['marital_status']);
});

it('creates an employee account that can log in with the generated id', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('employees.store'), validEmployeePayload(['email' => 'login.employee@example.com']))
        ->assertCreated();

    $employee = Employee::query()->where('client_id', $client->getKey())->firstOrFail();
    $this->post(route('logout'));
    $response = $this->post(route('login.store'), [
        'login' => $employee->employee_no,
        'password' => 'password',
    ]);

    $response->assertRedirectToRoute('dashboard');
    $this->assertAuthenticatedAs(User::query()->findOrFail($employee->user_id));
});

it('allows sim id and email to be left blank', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('employees.store'), validEmployeePayload(['sim_id' => '', 'email' => '']));

    $response->assertCreated();
});

it('updates an employee with sim id, email, and marital status', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey()]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('employees.update', $employee), validEmployeePayload(['marital_status' => 'divorced']));

    $response->assertOk();
    $this->assertDatabaseHas('employees', ['id' => $employee->getKey(), 'employee_no' => $employee->employee_no, 'marital_status' => 'divorced', 'sim_id' => 'PEG1234']);
    $this->assertDatabaseHas('users', ['id' => $employee->fresh()->user_id, 'name' => 'Ananda Julian', 'email' => 'ananda@example.com']);
});

it('includes humanized labels in the detail payload for the datatable', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $group = Group::factory()->create(['client_id' => $client->getKey(), 'name' => 'Group A']);
    Employee::factory()->create([
        'client_id' => $client->getKey(),
        'group_id' => $group->getKey(),
        'gender' => 'female',
        'employee_status' => 'contract',
        'marital_status' => 'widowed',
        'rate_category' => 'lama',
        'status' => 'active',
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('employees.index', ['draw' => 1, 'start' => 0, 'length' => 10]));

    $response->assertOk();
    $html = $response->json('data.0.action');

    expect($html)
        ->toContain('data-employee-detail=')
        ->toContain('&quot;gender&quot;:&quot;Perempuan&quot;')
        ->toContain('&quot;employee_status&quot;:&quot;Kontrak&quot;')
        ->toContain('&quot;marital_status&quot;:&quot;Cerai mati&quot;')
        ->toContain('&quot;group_name&quot;:&quot;Group A&quot;');
});
