<?php

use App\Models\Client;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('seeds a payroll-ready co-packing workspace with realistic records', function () {
    $this->seed(DatabaseSeeder::class);

    $this->assertDatabaseHas('clients', [
        'code' => 'CLIENT001',
        'name' => 'PT SIMGROUP Co-Packing',
    ]);
    expect(Hash::check('password', User::query()->where('username', 'superadmin')->value('password')))->toBeTrue();
    expect(User::query()->count())->toBe(11);
    expect(User::query()->where('is_super_admin', true)->value('username'))->toBe('superadmin');
    $this->assertDatabaseHas('users', ['username' => 'client.demo.01', 'status' => 'active']);
    $this->assertDatabaseCount('clients', 10);
    $this->assertDatabaseCount('client_user', 19);
    $this->assertDatabaseHas('products', ['sku' => 'KOP-3IN1-20G', 'name' => 'Kopi Sachet 3in1 20g']);
    $this->assertDatabaseHas('products', ['sku' => 'DET-BUB-1KG', 'employee_rate' => 260]);
    $this->assertDatabaseHas('work_realizations', ['is_complaint' => true]);

    $this->assertDatabaseCount('units', 10);
    $this->assertDatabaseCount('groups', 10);
    $this->assertDatabaseCount('shifts', 10);
    $this->assertDatabaseCount('cost_centers', 10);
    $this->assertDatabaseCount('employees', 10);
    $this->assertDatabaseCount('products', 10);
    $this->assertDatabaseCount('batches', 10);
    $this->assertDatabaseCount('work_realizations', 10);
    $this->assertDatabaseCount('realization_employees', 17);
    $this->assertDatabaseCount('deduction_periods', 1);
    $this->assertDatabaseCount('employee_deductions', 10);
    $this->assertDatabaseCount('audit_logs', 10);
});

it('rebuilds the seeded workspace without duplicating operational data', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Client::query()->count())->toBe(10);
    expect(User::query()->count())->toBe(11);
    $this->assertDatabaseCount('employees', 10);
    $this->assertDatabaseCount('products', 10);
    $this->assertDatabaseCount('work_realizations', 10);
    $this->assertDatabaseMissing('users', ['username' => 'karyawan.demo']);
    $this->assertDatabaseMissing('users', ['username' => 'client.demo']);
});
