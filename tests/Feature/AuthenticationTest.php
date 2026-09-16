<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the login page for guests', function () {
    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertSee('Masuk ke akun Anda')
        ->assertSee(asset('images/logo-white.png'), false)
        ->assertSee(asset('favicon.ico'), false)
        ->assertDontSee('Kode Client');
});

it('authenticates active users with a username or email and selects their default client', function (string $login) {
    $user = User::factory()->create([
        'username' => 'rani.kusuma',
        'email' => 'rani@example.com',
    ]);
    $client = Client::factory()->create(['code' => 'CLIENT001']);
    $role = Role::factory()->create();
    $user->clients()->attach($client, [
        'role_id' => $role->getKey(),
        'is_default' => true,
        'status' => 'active',
    ]);

    $response = $this->post(route('login.store'), [
        'login' => $login,
        'password' => 'password',
        'remember' => true,
    ]);

    $response
        ->assertRedirectToRoute('dashboard')
        ->assertSessionHas('current_client_id', $client->getKey())
        ->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
})->with([
    'username' => 'rani.kusuma',
    'email' => 'rani@example.com',
]);

it('rejects invalid credentials with a generic message', function () {
    User::factory()->create([
        'username' => 'rani.kusuma',
        'email' => 'rani@example.com',
    ]);
    $response = $this->from(route('login'))->post(route('login.store'), [
        'login' => 'rani.kusuma',
        'password' => 'not-the-password',
    ]);

    $response
        ->assertRedirectToRoute('login')
        ->assertSessionHasErrors(['login' => 'Username atau password tidak sesuai.']);

    $this->assertGuest();
});

it('rejects inactive accounts', function () {
    $client = Client::factory()->create();
    $user = User::factory()->inactive()->create(['username' => 'inactive.user']);
    $role = Role::factory()->create();
    $user->clients()->attach($client, [
        'role_id' => $role->getKey(),
        'is_default' => true,
        'status' => 'active',
    ]);

    $response = $this->from(route('login'))->post(route('login.store'), [
        'login' => 'inactive.user',
        'password' => 'password',
    ]);

    $response
        ->assertRedirectToRoute('login')
        ->assertSessionHasErrors(['login' => 'Username atau password tidak sesuai.']);

    $this->assertGuest();
});

it('rate limits repeated failed login attempts', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), [
            'login' => 'unknown@example.com',
            'password' => 'wrong',
        ]);
    }

    $response = $this->from(route('login'))->post(route('login.store'), [
        'login' => 'unknown@example.com',
        'password' => 'wrong',
    ]);

    $response
        ->assertRedirectToRoute('login')
        ->assertSessionHasErrors('login');
    expect($response->getSession()->get('errors')->first('login'))->toContain('Terlalu banyak percobaan masuk.');
});

it('logs out and invalidates the session', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => 12])
        ->post(route('logout'));

    $response
        ->assertRedirectToRoute('login')
        ->assertSessionMissing('current_client_id');
    $this->assertGuest();
});

it('rejects valid credentials when the user has no active client access', function () {
    User::factory()->create(['username' => 'rani.kusuma']);

    $response = $this->from(route('login'))->post(route('login.store'), [
        'login' => 'rani.kusuma',
        'password' => 'password',
    ]);

    $response
        ->assertRedirectToRoute('login')
        ->assertSessionHasErrors(['login' => 'Username atau password tidak sesuai.']);
    $this->assertGuest();
});

it('allows an employee profile to use a linked user account without an avatar', function () {
    $client = Client::factory()->create();
    $user = User::factory()->create(['avatar_path' => null]);
    $employee = Employee::factory()->for($client)->create(['user_id' => $user->getKey()]);

    expect($user->fresh()->employee->is($employee))->toBeTrue();
    expect($user->fresh()->avatar_path)->toBeNull();
});

it('authenticates a linked employee account with its client access', function () {
    $client = Client::factory()->create();
    $role = Role::factory()->create(['code' => 'operator']);
    $user = User::factory()->create([
        'username' => 'operator.siti',
        'email' => 'siti@example.com',
    ]);
    $user->clients()->attach($client, [
        'role_id' => $role->getKey(),
        'is_default' => true,
        'status' => 'active',
    ]);
    Employee::factory()->for($client)->create([
        'user_id' => $user->getKey(),
        'email' => $user->email,
    ]);

    $response = $this->post(route('login.store'), [
        'login' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertRedirectToRoute('dashboard')
        ->assertSessionHas('current_client_id', $client->getKey());
    $this->assertAuthenticatedAs($user);
});
