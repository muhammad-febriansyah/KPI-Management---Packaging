<?php

use App\Exports\InvoiceReportExport;
use App\Models\Client;
use App\Models\CostCenter;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use App\Models\WorkRealization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

function invoiceReportUser(Client $client, string $roleCode, bool $withMenu = true): User
{
    $role = Role::query()->firstOrCreate(['code' => $roleCode], ['name' => ucfirst($roleCode)]);

    if ($withMenu) {
        $role->permissions()->syncWithoutDetaching([Permission::query()->firstOrCreate(['code' => 'menu.invoice-report'], ['name' => 'Menu: Report Invoice'])->getKey()]);
    }

    $user = User::factory()->create();
    $user->clients()->attach($client, ['role_id' => $role->getKey(), 'is_default' => true, 'status' => 'active']);

    return $user;
}

/**
 * Records output for a SKU priced at the master PO price.
 */
function recordInvoiceOutput(Client $client, User $creator, string $sku, string $costCenterName, float $poPrice, string $workDate, float $output): void
{
    $unit = Unit::query()->withoutGlobalScopes()->firstOrCreate(['client_id' => $client->getKey(), 'code' => 'BOX'], ['name' => 'Box', 'status' => 'active']);
    $costCenter = CostCenter::query()->withoutGlobalScopes()->firstOrCreate(['client_id' => $client->getKey(), 'code' => strtoupper($costCenterName)], ['name' => $costCenterName, 'status' => 'active']);
    $product = Product::query()->withoutGlobalScopes()->firstOrCreate(
        ['client_id' => $client->getKey(), 'sku' => $sku],
        ['name' => 'Produk '.$sku, 'unit_id' => $unit->getKey(), 'cost_center_id' => $costCenter->getKey(), 'po_price' => $poPrice, 'employee_rate' => 0, 'status' => 'active'],
    );
    $shift = Shift::query()->withoutGlobalScopes()->firstOrCreate(['client_id' => $client->getKey(), 'code' => 'S1'], ['name' => 'Shift 1', 'start_time' => '08:00', 'end_time' => '16:00', 'status' => 'active']);

    WorkRealization::query()->withoutGlobalScopes()->create([
        'client_id' => $client->getKey(), 'work_date' => $workDate, 'shift_id' => $shift->getKey(), 'product_id' => $product->getKey(),
        'sku_snapshot' => $sku, 'product_name_snapshot' => $product->name, 'unit_name_snapshot' => 'Box', 'total_output' => $output, 'created_by' => $creator->getKey(),
    ]);
}

function invoiceReportRequest(User $user, Client $client, array $query = [])
{
    $columns = collect(['', 'period', 'sku', 'cost_center', 'qty', 'unit', 'po_price', 'total_po'])
        ->map(fn (string $name): array => ['data' => $name, 'searchable' => in_array($name, ['', 'qty', 'po_price', 'total_po'], true) ? 'false' : 'true', 'orderable' => $name === '' ? 'false' : 'true', 'search' => ['value' => '', 'regex' => 'false']])
        ->all();

    return test()->actingAs($user)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('reports.invoice').'?'.http_build_query($query + ['draw' => 1, 'start' => 0, 'length' => 10, 'order' => [['column' => 1, 'dir' => 'asc']], 'columns' => $columns]));
}

it('groups realizations by period, SKU, and cost center at the master PO price', function () {
    $client = Client::factory()->create();
    $creator = User::factory()->superAdmin()->create();
    $clientUser = invoiceReportUser($client, 'client');
    recordInvoiceOutput($client, $creator, 'SKU-A', 'Gudang', 1000, '2026-10-02', 10);
    recordInvoiceOutput($client, $creator, 'SKU-A', 'Gudang', 1000, '2026-10-20', 5.5);
    recordInvoiceOutput($client, $creator, 'SKU-A', 'Gudang', 1000, '2026-11-01', 3);
    recordInvoiceOutput($client, $creator, 'SKU-B', 'Produksi', 2500, '2026-10-05', 4);

    $response = invoiceReportRequest($clientUser, $client)->assertOk();
    $rows = collect($response->json('data'));

    expect($response->json('recordsTotal'))->toBe(3)
        ->and($rows->pluck('period')->all())->toBe(['10-2026', '10-2026', '11-2026'])
        ->and($rows->pluck('sku')->all())->toBe(['SKU-A', 'SKU-B', 'SKU-A'])
        ->and($rows->firstWhere('sku', 'SKU-A')['qty'])->toBe('15,5')
        ->and($rows->firstWhere('sku', 'SKU-A')['po_price'])->toBe('Rp 1.000')
        ->and($rows->firstWhere('sku', 'SKU-A')['total_po'])->toBe('Rp 15.500')
        ->and($rows->firstWhere('sku', 'SKU-B')['cost_center'])->toBe('Produksi')
        ->and($rows->firstWhere('sku', 'SKU-B')['total_po'])->toBe('Rp 10.000')
        ->and($rows->firstWhere('sku', 'SKU-B')['unit'])->toBe('Box')
        ->and($response->json('summary.total_po'))->toBe('Rp 28.500');
});

it('filters the report by period range and search', function () {
    $client = Client::factory()->create();
    $creator = User::factory()->superAdmin()->create();
    $clientUser = invoiceReportUser($client, 'client');
    recordInvoiceOutput($client, $creator, 'SKU-A', 'Gudang', 1000, '2026-09-10', 1);
    recordInvoiceOutput($client, $creator, 'SKU-A', 'Gudang', 1000, '2026-10-10', 2);
    recordInvoiceOutput($client, $creator, 'SKU-B', 'Produksi', 500, '2026-11-10', 4);

    invoiceReportRequest($clientUser, $client, ['period_from' => '2026-10', 'period_to' => '2026-10'])
        ->assertJsonPath('recordsTotal', 1)->assertJsonPath('data.0.total_po', 'Rp 2.000')->assertJsonPath('summary.total_po', 'Rp 2.000');
    invoiceReportRequest($clientUser, $client, ['period_from' => '2026-10'])->assertJsonPath('recordsTotal', 2)->assertJsonPath('summary.total_po', 'Rp 4.000');
    invoiceReportRequest($clientUser, $client, ['search' => ['value' => 'Produksi']])->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.sku', 'SKU-B');
    invoiceReportRequest($clientUser, $client, ['search' => ['value' => '09-2026']])->assertJsonPath('recordsFiltered', 1);
    invoiceReportRequest($clientUser, $client, ['period_from' => '2026-11', 'period_to' => '2026-10'])->assertUnprocessable();
});

it('only reports the active client\'s own realizations', function () {
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $creator = User::factory()->superAdmin()->create();
    $clientUser = invoiceReportUser($client, 'client');
    recordInvoiceOutput($client, $creator, 'SKU-OWN', 'Gudang', 1000, '2026-10-10', 1);
    recordInvoiceOutput($otherClient, $creator, 'SKU-FOREIGN', 'Gudang', 1000, '2026-10-10', 9);

    $response = invoiceReportRequest($clientUser, $client)->assertOk();

    expect(collect($response->json('data'))->pluck('sku')->all())->toBe(['SKU-OWN'])
        ->and($response->json('summary.total_po'))->toBe('Rp 1.000');
});

it('lets a client account open the report page and the nav shows it', function () {
    $client = Client::factory()->create();
    $clientUser = invoiceReportUser($client, 'client');

    $this->actingAs($clientUser)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('reports.invoice'))
        ->assertOk()
        ->assertSee('Report Invoice')
        ->assertSee('data-invoice-report-table', false)
        ->assertSee('href="'.route('reports.invoice').'"', false);
});

it('forbids the report and its export without the invoice report menu', function () {
    $client = Client::factory()->create();
    $user = invoiceReportUser($client, 'bare-invoice-report', withMenu: false);

    foreach (['reports.invoice', 'reports.invoice.export.excel'] as $route) {
        $this->actingAs($user)
            ->withSession(['current_client_id' => $client->getKey()])
            ->get(route($route))
            ->assertForbidden();
    }
});

it('exports the grouped report as an Excel file', function () {
    Excel::fake();
    $client = Client::factory()->create();
    $creator = User::factory()->superAdmin()->create();
    $clientUser = invoiceReportUser($client, 'client');
    recordInvoiceOutput($client, $creator, 'SKU-A', 'Gudang', 1000, '2026-10-10', 2);

    $this->actingAs($clientUser)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('reports.invoice.export.excel', ['period_from' => '2026-10', 'period_to' => '2026-10']))
        ->assertOk();

    Excel::assertDownloaded('report-invoice-2026-10-sd-2026-10.xlsx', function (InvoiceReportExport $export): bool {
        $rows = $export->query()->get()->map(fn (object $row): array => $export->map($row));

        return $export->headings() === ['No', 'Periode', 'SKU', 'Cost Center', 'Qty', 'Satuan', 'Harga PO', 'Total PO']
            && $rows->all() === [[1, '10-2026', 'SKU-A', 'Gudang', 2.0, 'Box', 1000, 2000]];
    });
});
