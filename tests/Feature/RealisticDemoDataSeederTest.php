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
    $this->assertDatabaseHas('users', ['username' => 'budi.santoso', 'status' => 'active']);
    $this->assertDatabaseHas('users', ['username' => 'fajar.ramadhan', 'status' => 'inactive']);
    expect(Hash::check('password', User::query()->where('username', 'superadmin')->value('password')))->toBeTrue();
    expect(Hash::check('password', User::query()->where('username', 'budi.santoso')->value('password')))->toBeTrue();
    expect(Hash::check('password', User::query()->where('username', 'andi.wijaya')->value('password')))->toBeTrue();
    $this->assertDatabaseHas('products', ['sku' => 'KOP-3IN1-20G', 'name' => 'Kopi Sachet 3in1 20g']);
    $this->assertDatabaseHas('products', ['sku' => 'DET-BUB-1KG', 'old_employee_rate' => 20000, 'new_employee_rate' => 25000]);
    $this->assertDatabaseHas('work_realizations', ['status' => 'submitted', 'is_complaint' => true]);

    $this->assertDatabaseCount('units', 5);
    $this->assertDatabaseCount('groups', 8);
    $this->assertDatabaseCount('cost_centers', 6);
    $this->assertDatabaseCount('employees', 12);
    $this->assertDatabaseCount('products', 12);
    $this->assertDatabaseCount('batches', 12);
    $this->assertDatabaseCount('work_realizations', 18);
    $this->assertDatabaseCount('realization_employees', 25);
    $this->assertDatabaseCount('deduction_periods', 2);
    $this->assertDatabaseCount('employee_deductions', 22);
});

it('rebuilds the seeded workspace without duplicating operational data', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Client::query()->count())->toBe(1);
    $this->assertDatabaseCount('employees', 12);
    $this->assertDatabaseCount('products', 12);
    $this->assertDatabaseCount('work_realizations', 18);
    $this->assertDatabaseMissing('users', ['username' => 'karyawan.demo']);
    $this->assertDatabaseMissing('users', ['username' => 'client.demo']);
});
