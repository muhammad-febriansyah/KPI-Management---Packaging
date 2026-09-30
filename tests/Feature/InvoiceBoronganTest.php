<?php

use App\Exports\InvoiceExport;
use App\Models\Client;
use App\Models\CostCenter;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

function makeInvoiceRealization(Client $client, User $creator, Product $product, Shift $shift, float $output, string $date, ?Employee $employee = null): int
{
    $realizationId = DB::table('work_realizations')->insertGetId([
        'client_id' => $client->getKey(),
        'work_date' => $date,
        'shift_id' => $shift->getKey(),
        'batch_id' => null,
        'product_id' => $product->getKey(),
        'sku_snapshot' => $product->sku,
        'product_name_snapshot' => $product->name,
        'unit_name_snapshot' => 'PCS',
        'total_output' => $output,
        'start_time' => '08:00',
        'end_time' => '16:00',
        'created_by' => $creator->getKey(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    if ($employee !== null) {
        DB::table('realization_employees')->insert([
            'client_id' => $client->getKey(),
            'work_realization_id' => $realizationId,
            'employee_id' => $employee->getKey(),
            'rate_category_snapshot' => $employee->rate_category,
            'rate_per_unit_snapshot' => 0,
            'allocation_output' => null,
            'gross_amount' => 0,
            'created_at' => now(),
        ]);
    }

    return $realizationId;
}

it('aggregates realization data into invoice borongan rows', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $unit = Unit::factory()->create(['client_id' => $client->getKey()]);
    $costCenter = CostCenter::factory()->create(['client_id' => $client->getKey()]);
    $shift = Shift::factory()->create(['client_id' => $client->getKey()]);
    $product = Product::factory()->create([
        'client_id' => $client->getKey(),
        'unit_id' => $unit->getKey(),
        'cost_center_id' => $costCenter->getKey(),
        'sku' => 'SKU-INVOICE-001',
        'po_price' => 2500,
    ]);
    $employee = Employee::factory()->create(['client_id' => $client->getKey()]);
    makeInvoiceRealization($client, $admin, $product, $shift, 10, '2026-09-08', $employee);
    makeInvoiceRealization($client, $admin, $product, $shift, 15, '2026-09-09', $employee);

    $response = $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('invoices.index').'?draw=1&start=0&length=10&period=2026-09');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.invoice', 'TBC')
        ->assertJsonPath('data.0.period', '09/2026')
        ->assertJsonPath('data.0.sku', 'SKU-INVOICE-001')
        ->assertJsonPath('data.0.qty', '25')
        ->assertJsonPath('data.0.manpower', 2)
        ->assertJsonPath('data.0.amount_po', 'Rp 62.500');
});

it('renders invoice borongan page with client filters', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();

    $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('invoices.index'))
        ->assertOk()
        ->assertSee('Invoice Borongan')
        ->assertSee('data-invoice-filters', false);
});

it('keeps invoice borongan data within selected client', function () {
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $otherClient = Client::factory()->create();
    $unit = Unit::factory()->create(['client_id' => $client->getKey()]);
    $otherUnit = Unit::factory()->create(['client_id' => $otherClient->getKey()]);
    $shift = Shift::factory()->create(['client_id' => $client->getKey()]);
    $otherShift = Shift::factory()->create(['client_id' => $otherClient->getKey()]);
    $product = Product::factory()->create(['client_id' => $client->getKey(), 'unit_id' => $unit->getKey(), 'sku' => 'ACTIVE-CLIENT']);
    $otherProduct = Product::factory()->create(['client_id' => $otherClient->getKey(), 'unit_id' => $otherUnit->getKey(), 'sku' => 'OTHER-CLIENT']);
    makeInvoiceRealization($client, $admin, $product, $shift, 10, '2026-09-08');
    makeInvoiceRealization($otherClient, $admin, $otherProduct, $otherShift, 20, '2026-09-08');

    $response = $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->getJson(route('invoices.index').'?draw=1&start=0&length=10&period=2026-09');

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.sku', 'ACTIVE-CLIENT');
});

it('exports invoice borongan using selected filters', function () {
    Excel::fake();
    $admin = User::factory()->superAdmin()->create();
    $client = Client::factory()->create();
    $unit = Unit::factory()->create(['client_id' => $client->getKey()]);
    $shift = Shift::factory()->create(['client_id' => $client->getKey()]);
    $product = Product::factory()->create(['client_id' => $client->getKey(), 'unit_id' => $unit->getKey()]);
    makeInvoiceRealization($client, $admin, $product, $shift, 10, '2026-09-08');

    $response = $this->actingAs($admin)
        ->withSession(['current_client_id' => $client->getKey()])
        ->get(route('invoices.export.excel', ['period' => '2026-09']));

    $response->assertOk();
    Excel::assertDownloaded('invoice-borongan-2026-09.xlsx', function (InvoiceExport $export): bool {
        $row = $export->query()->first();

        return $export->headings()[1] === 'Invoice'
            && $row !== null
            && $row->invoice === 'TBC';
    });
});
