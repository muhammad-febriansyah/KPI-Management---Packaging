<?php

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

it('renders audit date filters as date pickers', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('audit.index'));

    $response->assertOk()
        ->assertSee('data-audit-date-from data-datepicker type="text"', false)
        ->assertSee('data-audit-date-to data-datepicker type="text"', false)
        ->assertSee('href="'.route('audit.index').'"', false)
        ->assertSee('Semua aktivitas')
        ->assertSee('Data dibuat')
        ->assertSee('Keluar dari sistem')
        ->assertSee('Data diimpor')
        ->assertSee('Aktivitas')
        ->assertDontSee('data-audit-date-from type="date"', false)
        ->assertDontSee('data-audit-date-to type="date"', false);
});

it('describes audit activities in plain Indonesian', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    AuditLog::query()->create([
        'client_id' => $client->getKey(),
        'user_id' => $user->getKey(),
        'action' => 'login',
        'auditable_type' => User::class,
        'auditable_id' => $user->getKey(),
        'ip_address' => '127.0.0.1',
    ]);
    AuditLog::query()->create([
        'client_id' => $client->getKey(),
        'user_id' => $user->getKey(),
        'action' => 'update',
        'auditable_type' => 'App\\Models\\WorkRealization',
        'auditable_id' => 10,
        'ip_address' => '127.0.0.1',
    ]);
    AuditLog::query()->create([
        'client_id' => $client->getKey(),
        'user_id' => $user->getKey(),
        'action' => 'create',
        'auditable_type' => 'App\\Models\\Product',
        'auditable_id' => 11,
        'ip_address' => '127.0.0.1',
    ]);

    $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('audit.index', ['draw' => 1, 'start' => 0, 'length' => 10]))
        ->assertOk()
        ->assertJsonFragment(['description' => 'Masuk ke sistem'])
        ->assertJsonFragment(['description' => 'Mengubah realisasi pekerjaan'])
        ->assertJsonFragment(['description' => 'Menambahkan produk']);
});

it('hides audit log navigation from non-super-admin users', function () {
    $client = Client::factory()->create();
    $role = Role::query()->create(['code' => 'dashboard-only', 'name' => 'Dashboard Only']);
    $permission = Permission::query()->firstOrCreate(['code' => 'menu.dashboard'], ['name' => 'Menu: Dashboard']);
    $role->permissions()->attach($permission);
    $user = User::factory()->create();
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);

    $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('href="'.route('audit.index').'"', false);
});

it('records a successful write with the response message, client, and IP', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->withServerVariables(['REMOTE_ADDR' => '10.1.2.3'])
        ->postJson(route('groups.store'), ['client_id' => $client->getKey(), 'code' => 'PACK', 'name' => 'Packing', 'status' => 'active'])
        ->assertOk();

    $log = AuditLog::query()->sole();
    expect($log->action)->toBe('create')
        ->and($log->user_id)->toBe($user->getKey())
        ->and($log->client_id)->toBe($client->getKey())
        ->and($log->ip_address)->toBe('10.1.2.3')
        ->and($log->created_at)->not->toBeNull()
        ->and($log->new_values['description'])->toBe($response->json('message'));
});

it('records updates, deletes, and logouts but ignores reads and failed requests', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $group = Group::factory()->create(['client_id' => $client->getKey()]);
    $session = ['current_client_id' => $client->getKey()];

    $this->actingAs($user)->withSession($session)->getJson(route('groups.index', ['draw' => 1, 'start' => 0, 'length' => 10]))->assertOk();
    $this->actingAs($user)->withSession($session)->postJson(route('groups.store'), [])->assertUnprocessable();
    expect(AuditLog::query()->count())->toBe(0);

    $this->actingAs($user)->withSession($session)->putJson(route('groups.update', $group), ['code' => $group->code, 'name' => 'Nama Baru', 'status' => 'active'])->assertOk();
    $this->actingAs($user)->withSession($session)->deleteJson(route('groups.destroy', $group))->assertOk();
    $this->actingAs($user)->withSession($session)->post(route('logout'));

    expect(AuditLog::query()->orderBy('id')->pluck('action')->all())->toBe(['update', 'delete', 'logout']);
});

it('records an import with the import action', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray([['Client Code', 'Group Code', 'Nama Group', 'Status'], [$client->code, 'PACK', 'Packing', 'active']]);
    $path = tempnam(sys_get_temp_dir(), 'audit-import').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->postJson(route('groups.import'), ['file' => new UploadedFile($path, 'import.xlsx', null, null, true)])
        ->assertOk();

    $log = AuditLog::query()->sole();
    expect($log->action)->toBe('import')
        ->and($log->new_values['description'])->toContain('group');
});

it('shows audit logs from every client to a super admin', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    foreach ([$client, $otherClient] as $target) {
        AuditLog::query()->create(['client_id' => $target->getKey(), 'user_id' => $user->getKey(), 'action' => 'create', 'auditable_type' => Group::class, 'auditable_id' => 1, 'new_values' => ['description' => 'Membuat grup '.$target->code]]);
    }

    $descriptions = collect($this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('audit.index', ['draw' => 1, 'start' => 0, 'length' => 10]))
        ->assertOk()
        ->json('data'))->pluck('description')->all();

    expect($descriptions)->toContain('Membuat grup '.$client->code, 'Membuat grup '.$otherClient->code);
});
