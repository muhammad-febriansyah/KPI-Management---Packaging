<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('redirects guests to login from the root page', function () {
    $response = $this->get('/');

    $response->assertRedirectToRoute('login');
});

it('lists clients as json for the datatable', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create(['name' => 'PT Sumber Makmur']);

    $response = $this->actingAs($admin)->getJson(route('clients.index', ['draw' => 1, 'start' => 0, 'length' => 10]));

    $response->assertOk()->assertJsonFragment(['name' => 'PT Sumber Makmur']);
});

it('hides timezone from the client management page', function () {
    $admin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($admin)->get(route('clients.index'));

    $response->assertOk()->assertDontSee('Timezone');
});

it('lets a super admin create a client via the ajax modal', function () {
    $admin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($admin)->postJson(route('clients.store'), [
        'code' => 'KP-001', 'name' => 'PT Contoh', 'account_name' => 'PIC Contoh',
        'login_username' => 'pic.contoh', 'login_email' => 'pic.contoh@example.com',
        'password' => 'secret123', 'password_confirmation' => 'secret123', 'status' => 'active',
    ]);

    $response->assertCreated()->assertJsonPath('message', 'Client berhasil ditambahkan.');
    $this->assertDatabaseHas('clients', ['code' => 'KP-001', 'name' => 'PT Contoh']);
    $this->assertDatabaseHas('users', ['username' => 'pic.contoh', 'email' => 'pic.contoh@example.com', 'name' => 'PIC Contoh']);
});

it('rejects a non super admin from creating a client', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('clients.store'), [
        'code' => 'KP-001', 'name' => 'PT Contoh', 'account_name' => 'PIC Contoh',
        'login_username' => 'pic.contoh', 'login_email' => 'pic.contoh@example.com',
        'password' => 'secret123', 'password_confirmation' => 'secret123', 'status' => 'active',
    ]);

    $response->assertForbidden();
});

it('updates a client via the ajax modal with method spoofing', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create(['name' => 'Nama Lama']);

    $response = $this->actingAs($admin)->postJson(route('clients.update', $client), [
        '_method' => 'PUT', 'code' => $client->code, 'name' => 'Nama Baru', 'account_name' => 'PIC Baru',
        'login_username' => 'pic.baru', 'login_email' => 'pic.baru@example.com',
        'password' => 'secret123', 'password_confirmation' => 'secret123', 'status' => 'active',
    ]);

    $response->assertOk()->assertJsonPath('message', 'Client berhasil diperbarui.');
    $this->assertDatabaseHas('clients', ['id' => $client->getKey(), 'name' => 'Nama Baru']);
    $this->assertDatabaseHas('users', ['username' => 'pic.baru', 'email' => 'pic.baru@example.com', 'name' => 'PIC Baru']);
});

it('creates a client account that can log in without entering a client code', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)->postJson(route('clients.store'), [
        'code' => 'KP-LOGIN', 'name' => 'PT Login', 'account_name' => 'PIC Login',
        'login_username' => 'pic.login', 'login_email' => 'pic.login@example.com',
        'password' => 'secret123', 'password_confirmation' => 'secret123', 'status' => 'active',
    ])->assertCreated();

    $this->post(route('logout'));

    $response = $this->post(route('login.store'), [
        'login' => 'pic.login',
        'password' => 'secret123',
    ]);

    $response->assertRedirectToRoute('dashboard');
    $this->assertAuthenticated();
    expect(session('current_client_id'))->toBe(Client::query()->where('code', 'KP-LOGIN')->value('id'));
});

it('deletes a client via ajax', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($admin)->deleteJson(route('clients.destroy', $client));

    $response->assertOk()->assertJsonPath('message', 'Client berhasil dihapus.');
    $this->assertSoftDeleted('clients', ['id' => $client->getKey()]);
});
