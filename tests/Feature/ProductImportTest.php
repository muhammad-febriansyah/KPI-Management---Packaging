<?php

use App\Exports\ProductTemplateExport;
use App\Models\Client;
use App\Models\CostCenter;
use App\Models\Group;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

uses(RefreshDatabase::class);

it('shows product import controls', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('products.index'))
        ->assertOk()
        ->assertSee('data-product-import-open', false)
        ->assertSee('data-product-import-form', false)
        ->assertSee(route('products.template'), false);
});

function buildProductImportFile(array $rows): UploadedFile
{
    $headings = ['Client Code', 'SKU', 'Nama Produk', 'Kode Satuan', 'Kode Group', 'Kode Cost Center', 'Harga PO', 'Tarif Karyawan', 'Output per Jam', 'Status'];
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray([$headings, ...$rows]);
    $path = tempnam(sys_get_temp_dir(), 'product-import').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'import.xlsx', null, null, true);
}

it('downloads a product import template with code-based master columns', function () {
    Excel::fake();
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create(['code' => 'CL-PROD']);
    $unit = Unit::factory()->create(['client_id' => $client->getKey(), 'code' => 'PCS']);
    $group = Group::factory()->create(['client_id' => $client->getKey(), 'code' => 'FOOD']);
    $costCenter = CostCenter::factory()->create(['client_id' => $client->getKey(), 'code' => 'PACK']);

    $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('products.template'))
        ->assertOk();

    Excel::assertDownloaded('template-master-produk.xlsx', function (ProductTemplateExport $export) use ($client, $unit, $group, $costCenter): bool {
        return $export->headings() === ['Client Code', 'SKU', 'Nama Produk', 'Kode Satuan', 'Kode Group', 'Kode Cost Center', 'Harga PO', 'Tarif Karyawan', 'Output per Jam', 'Status']
            && $export->array()[0] === [$client->code, 'SKU-CONTOH-001', 'Nama Produk Contoh', $unit->code, $group->code, $costCenter->code, 0, 0, 0, 'active'];
    });
});

it('imports products by resolving client and master codes', function () {
    $user = User::factory()->superAdmin()->create();
    $currentClient = Client::factory()->create(['code' => 'CL-CURRENT']);
    $targetClient = Client::factory()->create(['code' => 'CL-TARGET']);
    $unit = Unit::factory()->create(['client_id' => $targetClient->getKey(), 'code' => 'PCS']);
    $group = Group::factory()->create(['client_id' => $targetClient->getKey(), 'code' => 'FOOD']);
    $costCenter = CostCenter::factory()->create(['client_id' => $targetClient->getKey(), 'code' => 'PACK']);
    $file = buildProductImportFile([
        [$targetClient->code, 'SKU-001', 'Kopi Sachet', $unit->code, $group->code, $costCenter->code, 15000, 550.75, 120, 'active'],
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $currentClient->getKey()])
        ->post(route('products.import'), ['file' => $file]);

    $response->assertOk()->assertJson(['message' => '1 baris produk berhasil diimpor.']);
    $this->assertDatabaseHas('products', [
        'client_id' => $targetClient->getKey(),
        'sku' => 'SKU-001',
        'unit_id' => $unit->getKey(),
        'group_id' => $group->getKey(),
        'cost_center_id' => $costCenter->getKey(),
        'employee_rate' => 550.750,
    ]);
});

it('reports invalid master codes while importing valid product rows', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create(['code' => 'CL-PROD']);
    $unit = Unit::factory()->create(['client_id' => $client->getKey(), 'code' => 'PCS']);
    $file = buildProductImportFile([
        [$client->code, 'SKU-VALID', 'Produk Valid', $unit->code, '', '', 0, 0, 0, 'active'],
        [$client->code, 'SKU-INVALID', 'Produk Invalid', 'UNKNOWN', '', '', 0, 0, 0, 'active'],
    ]);

    $response = $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->post(route('products.import'), ['file' => $file]);

    $response->assertStatus(207)->assertJsonPath('failures.0.row', 3);
    expect($response->json('failures.0.errors.0'))->toContain('Kode satuan "UNKNOWN"');
    $this->assertDatabaseHas('products', ['client_id' => $client->getKey(), 'sku' => 'SKU-VALID']);
    $this->assertDatabaseMissing('products', ['client_id' => $client->getKey(), 'sku' => 'SKU-INVALID']);
});

it('uses the active client when client code is blank', function () {
    $user = User::factory()->superAdmin()->create();
    $client = Client::factory()->create(['code' => 'CL-PROD']);
    $unit = Unit::factory()->create(['client_id' => $client->getKey(), 'code' => 'PCS']);
    $file = buildProductImportFile([
        ['', 'SKU-ACTIVE', 'Produk Client Aktif', $unit->code, '', '', 0, 0, 0, 'active'],
    ]);

    $this->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->post(route('products.import'), ['file' => $file])
        ->assertOk();

    $this->assertDatabaseHas('products', ['client_id' => $client->getKey(), 'sku' => 'SKU-ACTIVE']);
});
