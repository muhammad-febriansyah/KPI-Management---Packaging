<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('allows every active user to open the profile page without a client context', function () {
    $user = User::factory()->create([
        'name' => 'User Tanpa Client',
        'username' => 'user.tanpa.client',
    ]);

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertOk();
    $response->assertSee('Profil Saya');
    $response->assertSee('User Tanpa Client');
});

it('updates profile details and avatar', function () {
    Storage::fake('public');
    $user = User::factory()->create(['avatar_path' => null]);
    $avatar = UploadedFile::fake()->image('avatar.png');

    $response = $this->actingAs($user)->put(route('profile.update'), [
        'name' => 'Nama Diperbarui',
        'username' => 'nama.diperbarui',
        'email' => 'nama.diperbarui@example.com',
        'avatar' => $avatar,
    ]);

    $response->assertRedirectToRoute('profile.edit');
    $updatedUser = $user->fresh();

    expect($updatedUser->name)->toBe('Nama Diperbarui');
    expect($updatedUser->avatar_path)->toStartWith('avatars/');
    Storage::disk('public')->assertExists($updatedUser->avatar_path);
});

it('shows employee table fields and updates only editable employee details', function () {
    $user = User::factory()->create([
        'name' => 'Karyawan Lama',
        'username' => '123456789',
        'email' => 'lama@example.com',
    ]);
    $client = Client::factory()->create();
    $role = Role::factory()->create(['code' => 'employee']);
    $employee = Employee::factory()->create([
        'client_id' => $client->getKey(),
        'user_id' => $user->getKey(),
        'employee_no' => '123456789',
        'full_name' => 'Karyawan Lama',
        'email' => 'lama@example.com',
        'sim_id' => 'SIM-LAMA',
        'phone' => '081111111111',
        'marital_status' => 'single',
    ]);
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);

    $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Data karyawan')
        ->assertSee('data-profile-avatar-preview');

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->put(route('profile.update'), [
            'name' => 'Karyawan Baru',
            'username' => 'username-tidak-boleh-diubah',
            'email' => 'baru@example.com',
            'sim_id' => 'SIM-BARU',
            'phone' => '082222222222',
            'marital_status' => 'married',
        ]);

    $response->assertRedirectToRoute('profile.edit');
    expect($employee->fresh()->full_name)->toBe('Karyawan Baru');
    expect($employee->fresh()->sim_id)->toBe('SIM-BARU');
    expect($employee->fresh()->phone)->toBe('082222222222');
    expect($employee->fresh()->marital_status)->toBe('married');
    expect($user->fresh()->username)->toBe('123456789');
    expect($user->fresh()->email)->toBe('baru@example.com');
});
