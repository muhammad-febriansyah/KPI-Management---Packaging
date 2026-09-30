<?php

use App\Models\Client;
use App\Models\DeductionPeriod;
use App\Models\Employee;
use App\Models\EmployeeDeduction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeEmployeeDeduction(Client $client, User $user, array $overrides = []): EmployeeDeduction
{
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'employee_no' => 'EMP001', 'sim_id' => 'PEG1234', 'full_name' => 'Ananda Julian']);
    $period = DeductionPeriod::query()->create(['client_id' => $client->getKey(), 'month' => '2026-08-01', 'week_no' => 2, 'status' => 'draft', 'created_by' => $user->getKey()]);

    return $period->deductions()->create([...['client_id' => $client->getKey(), 'employee_id' => $employee->getKey(), 'uniform_amount' => 50000, 'bpjs_health_percent' => 1, 'bpjs_employment_percent' => 2], ...$overrides]);
}

it('renders the deduction create page for the current client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    Employee::factory()->create(['client_id' => $client->getKey(), 'status' => 'active']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('deductions.create'));

    $response->assertOk();
    $response->assertSee('Tambah Potongan Gaji');
    $response->assertSee('data-deduction-create-form', false);
    $response->assertSee('name="client_id"', false);
    $response->assertSee('data-deduction-client', false);
    $response->assertSee('data-deduction-employee-count', false);
    $response->assertSee('name="month" data-datepicker', false);
    $response->assertSee(route('deductions.store'), false);
});

it('only offers deduction weeks one and two', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('deductions.create'));

    $response->assertOk()
        ->assertSee('Minggu 1')
        ->assertSee('Minggu 2')
        ->assertDontSee('Minggu 3')
        ->assertDontSee('Minggu 4')
        ->assertDontSee('Minggu 5');
});

it('rejects deduction weeks beyond week two', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'status' => 'active']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->post(route('deductions.store'), [
            'month' => '2026-09',
            'week_no' => '3',
            'employee_ids' => [$employee->getKey()],
        ]);

    $response->assertSessionHasErrors('week_no');
    expect(DeductionPeriod::query()->where('client_id', $client->getKey())->exists())->toBeFalse();
});

it('stores deductions from the create page and redirects to the index', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'status' => 'active']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->post(route('deductions.store'), [
            'month' => '2026-09',
            'week_no' => '2',
            'uniform_amount' => '50000',
            'employee_ids' => [$employee->getKey()],
        ]);

    $response->assertRedirect(route('deductions.index'));
    $this->assertDatabaseHas('deduction_periods', ['client_id' => $client->getKey(), 'month' => '2026-09-01 00:00:00', 'week_no' => 2]);
    $this->assertDatabaseHas('employee_deductions', ['client_id' => $client->getKey(), 'employee_id' => $employee->getKey(), 'uniform_amount' => 50000]);
});

it('rejects a duplicate deduction period before inserting', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'status' => 'active']);
    $period = DeductionPeriod::query()->create([
        'client_id' => $client->getKey(),
        'month' => '2026-09-01',
        'week_no' => null,
        'status' => 'locked',
        'created_by' => $user->getKey(),
    ]);
    $period->deductions()->create(['client_id' => $client->getKey(), 'employee_id' => $employee->getKey()]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('deductions.store'), ['month' => '2026-09', 'employee_ids' => [$employee->getKey()]]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['employee_ids']);
    expect(DeductionPeriod::query()->where('client_id', $client->getKey())->count())->toBe(1)
        ->and(EmployeeDeduction::query()->where('employee_id', $employee->getKey())->count())->toBe(1);
});

it('allows different employees in the same deduction period', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employees = Employee::factory()->count(2)->create(['client_id' => $client->getKey(), 'status' => 'active']);

    foreach ($employees as $employee) {
        $response = $this->actingAs($user)
            ->withSession(['current_client_id' => $client->getKey()])
            ->postJson(route('deductions.store'), [
                'client_id' => $client->getKey(),
                'month' => '2026-09',
                'week_no' => 1,
                'employee_ids' => [$employee->getKey()],
            ]);

        $response->assertCreated();
    }

    expect(DeductionPeriod::query()->where('client_id', $client->getKey())->count())->toBe(1)
        ->and(EmployeeDeduction::query()->where('client_id', $client->getKey())->count())->toBe(2);
});

it('returns employees only for the selected client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'employee_no' => 'EMPCUR001', 'status' => 'active']);
    $otherEmployee = Employee::factory()->create(['client_id' => $otherClient->getKey(), 'employee_no' => 'EMPTAR001', 'status' => 'active']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('deductions.employee-options', ['client_id' => $otherClient->getKey()]));

    $response->assertOk()->assertJsonPath('results.0.id', $otherEmployee->getKey());
    expect($response->json('results'))->not->toContain(['id' => $employee->getKey(), 'text' => "{$employee->employee_no} — {$employee->full_name}"]);
});

it('limits salary advance percentages to 100', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $employee = Employee::factory()->create(['client_id' => $client->getKey(), 'status' => 'active']);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->post(route('deductions.store'), [
            'month' => '2026-09',
            'salary_advance_type' => 'percentage',
            'salary_advance_value' => 100.001,
            'employee_ids' => [$employee->getKey()],
        ]);

    $response->assertSessionHasErrors('salary_advance_value');
});

it('lists employee deduction rows per employee for the current client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    makeEmployeeDeduction($client, $user);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('deductions.index').'?draw=1&start=0&length=10');

    $response->assertOk();
    $response->assertJsonPath('data.0.client_code', $client->code);
    $response->assertJsonPath('data.0.bulan', '2026-08');
    $response->assertJsonPath('data.0.minggu', 2);
    $response->assertJsonPath('data.0.no_karyawan', 'EMP001');
    $response->assertJsonPath('data.0.sim_id', 'PEG1234');
    $response->assertJsonPath('data.0.full_name', 'Ananda Julian');
    $response->assertJsonPath('data.0.uniform_amount', 50000);
    $response->assertJsonPath('data.0.periode', 'Agustus 2026 (Minggu 2)');
    $response->assertJsonPath('data.0.bpjs_health', '1%');
});

it('keeps decimals in the BPJS percentage only when the rate actually has one', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    makeEmployeeDeduction($client, $user, ['bpjs_health_percent' => 1.5, 'bpjs_employment_percent' => 2]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('deductions.index').'?draw=1&start=0&length=10');

    $response->assertOk();
    $response->assertJsonPath('data.0.bpjs_health', '1.5%');
    $response->assertJsonPath('data.0.bpjs_employment', '2%');
});

it('updates a single employee deduction row', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $deduction = makeEmployeeDeduction($client, $user);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('deductions.update', $deduction), [
            'uniform_amount' => 75000,
            'correction_plus' => 10000,
        ]);

    $response->assertOk();
    $this->assertDatabaseHas('employee_deductions', ['id' => $deduction->getKey(), 'uniform_amount' => 75000, 'correction_plus' => 10000]);
});

it('deletes a single employee deduction row', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $deduction = makeEmployeeDeduction($client, $user);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->deleteJson(route('deductions.destroy', $deduction));

    $response->assertOk();
    $this->assertDatabaseMissing('employee_deductions', ['id' => $deduction->getKey()]);
});

it('prevents updating a deduction row belonging to another client', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $deduction = makeEmployeeDeduction($otherClient, $user);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->putJson(route('deductions.update', $deduction), ['uniform_amount' => 1000]);

    $response->assertNotFound();
});
