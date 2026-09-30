<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\CostCenter;
use App\Models\Group;
use App\Models\Product;
use App\Models\Scopes\ClientScope;
use App\Models\Unit;
use App\Services\CurrentClientService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    /** @var array<int, array{row: int, errors: array<int, string>}> */
    public array $failures = [];

    public int $imported = 0;

    /** @param array<int, int> $allowedClientIds */
    public function __construct(
        private readonly int $currentClientId,
        private readonly array $allowedClientIds,
    ) {}

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows): void {
            $clients = Client::query()
                ->active()
                ->whereIn('id', $this->allowedClientIds)
                ->get()
                ->keyBy(fn (Client $client): string => mb_strtolower($client->code));
            $clientsById = $clients->keyBy('id');
            $units = $this->masterMap(Unit::class);
            $groups = $this->masterMap(Group::class);
            $costCenters = $this->masterMap(CostCenter::class);
            $seenProducts = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $data = $this->normalizeRow($row->toArray());
                $validator = Validator::make($data, [
                    'client_code' => ['nullable', 'string', 'max:50'],
                    'sku' => ['required', 'string', 'max:100'],
                    'name' => ['required', 'string', 'max:180'],
                    'unit_code' => ['required', 'string', 'max:30'],
                    'group_code' => ['nullable', 'string', 'max:50'],
                    'cost_center_code' => ['nullable', 'string', 'max:50'],
                    'po_price' => ['nullable', 'numeric', 'min:0'],
                    'employee_rate' => ['nullable', 'numeric', 'min:0'],
                    'estimated_output_per_hour' => ['nullable', 'integer', 'min:0'],
                    'status' => ['nullable', Rule::in(['active', 'inactive'])],
                ], [], [
                    'client_code' => 'Client Code', 'sku' => 'SKU', 'name' => 'Nama Produk',
                    'unit_code' => 'Kode Satuan', 'group_code' => 'Kode Group',
                    'cost_center_code' => 'Kode Cost Center', 'po_price' => 'Harga PO',
                    'employee_rate' => 'Tarif Karyawan', 'estimated_output_per_hour' => 'Output per Jam',
                    'status' => 'Status',
                ]);

                if ($validator->fails()) {
                    $this->addFailure($rowNumber, $validator->errors()->all());

                    continue;
                }

                $client = filled($data['client_code'])
                    ? $clients->get(mb_strtolower($data['client_code']))
                    : $clientsById->get($this->currentClientId);

                if (! $client) {
                    $this->addFailure($rowNumber, [sprintf('Client dengan kode "%s" tidak ditemukan atau tidak dapat diakses.', $data['client_code'] ?: $this->currentClientId)]);

                    continue;
                }

                $productKey = $client->id.'|'.mb_strtolower($data['sku']);
                if (isset($seenProducts[$productKey])) {
                    $this->addFailure($rowNumber, [sprintf('SKU "%s" duplikat pada file import (baris %d).', $data['sku'], $seenProducts[$productKey])]);

                    continue;
                }
                $seenProducts[$productKey] = $rowNumber;

                $unit = $units[$client->id.'|'.mb_strtolower($data['unit_code'])] ?? null;
                if (! $unit) {
                    $this->addFailure($rowNumber, [sprintf('Kode satuan "%s" tidak ditemukan atau nonaktif pada client %s.', $data['unit_code'], $client->code)]);

                    continue;
                }

                $group = $this->resolveOptionalMaster($groups, $data['group_code'] ?? null, $client, 'group');
                if ($group['error']) {
                    $this->addFailure($rowNumber, [$group['error']]);

                    continue;
                }

                $costCenter = $this->resolveOptionalMaster($costCenters, $data['cost_center_code'] ?? null, $client, 'cost center');
                if ($costCenter['error']) {
                    $this->addFailure($rowNumber, [$costCenter['error']]);

                    continue;
                }

                $payload = [
                    'client_id' => $client->id,
                    'sku' => $data['sku'],
                    'name' => $data['name'],
                    'unit_id' => $unit->id,
                    'group_id' => $group['model']?->id,
                    'cost_center_id' => $costCenter['model']?->id,
                    'po_price' => $data['po_price'] ?? 0,
                    'employee_rate' => $data['employee_rate'] ?? 0,
                    'estimated_output_per_hour' => $data['estimated_output_per_hour'],
                    'status' => $data['status'] ?: 'active',
                ];

                app(CurrentClientService::class)->runAs($client, function () use ($payload): void {
                    Product::query()->updateOrCreate(
                        ['client_id' => $payload['client_id'], 'sku' => $payload['sku']],
                        $payload,
                    );
                });

                $this->imported++;
            }
        });
    }

    /** @param class-string<Unit|Group|CostCenter> $model */
    private function masterMap(string $model): array
    {
        return $model::query()
            ->withoutGlobalScope(ClientScope::class)
            ->active()
            ->whereIn('client_id', $this->allowedClientIds)
            ->get()
            ->mapWithKeys(fn (Unit|Group|CostCenter $master): array => [$master->client_id.'|'.mb_strtolower((string) $master->code) => $master])
            ->all();
    }

    /** @return array{model: Unit|Group|CostCenter|null, error: ?string} */
    private function resolveOptionalMaster(array $masters, ?string $code, Client $client, string $label): array
    {
        if (! filled($code)) {
            return ['model' => null, 'error' => null];
        }

        $master = $masters[$client->id.'|'.mb_strtolower($code)] ?? null;

        return [
            'model' => $master,
            'error' => $master ? null : sprintf('Kode %s "%s" tidak ditemukan atau nonaktif pada client %s.', $label, $code, $client->code),
        ];
    }

    /** @param array<string, mixed> $data */
    private function normalizeRow(array $data): array
    {
        return [
            'client_code' => $this->normalizeCode($data['client_code'] ?? $data['kode_client'] ?? null),
            'sku' => $this->normalizeCode($data['sku'] ?? $data['kode_produk'] ?? null),
            'name' => trim((string) ($data['name'] ?? $data['nama_produk'] ?? '')),
            'unit_code' => $this->normalizeCode($data['unit_code'] ?? $data['kode_satuan'] ?? null),
            'group_code' => $this->normalizeCode($data['group_code'] ?? $data['kode_group'] ?? null),
            'cost_center_code' => $this->normalizeCode($data['cost_center_code'] ?? $data['kode_cost_center'] ?? null),
            'po_price' => $this->normalizeNumber($data['po_price'] ?? $data['harga_po'] ?? null),
            'employee_rate' => $this->normalizeNumber($data['employee_rate'] ?? $data['tarif_karyawan'] ?? null),
            'estimated_output_per_hour' => $this->normalizeNumber($data['estimated_output_per_hour'] ?? $data['output_per_jam'] ?? null),
            'status' => trim((string) ($data['status'] ?? '')) ?: null,
        ];
    }

    private function normalizeCode(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeNumber(mixed $value): mixed
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        $value = str_replace(['Rp', 'rp', ' '], '', trim((string) $value));
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = strrpos($value, ',') > strrpos($value, '.')
                ? str_replace(',', '.', str_replace('.', '', $value))
                : str_replace(',', '', $value);
        } else {
            $value = str_replace(',', '.', $value);
        }

        return $value;
    }

    /** @param array<int, string> $errors */
    private function addFailure(int $row, array $errors): void
    {
        $this->failures[] = ['row' => $row, 'errors' => $errors];
    }
}
