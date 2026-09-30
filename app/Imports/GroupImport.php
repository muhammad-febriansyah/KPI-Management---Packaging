<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\Group;
use App\Models\Scopes\ClientScope;
use App\Services\CurrentClientService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GroupImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
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
            $clients = Client::query()->active()->whereIn('id', $this->allowedClientIds)->get()->keyBy(fn (Client $client): string => mb_strtolower($client->code));
            $clientsById = $clients->keyBy('id');
            $groups = Group::query()->withoutGlobalScope(ClientScope::class)->whereIn('client_id', $this->allowedClientIds)->get()->mapWithKeys(fn (Group $group): array => [$group->client_id.'|'.mb_strtolower((string) $group->code) => $group])->all();
            $seen = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $data = $this->normalizeRow($row->toArray());
                $validator = Validator::make($data, [
                    'client_code' => ['nullable', 'string', 'max:50'],
                    'code' => ['required', 'string', 'max:50'],
                    'name' => ['required', 'string', 'max:100'],
                    'status' => ['nullable', Rule::in(['active', 'inactive'])],
                ], [], ['client_code' => 'Client Code', 'code' => 'Group Code', 'name' => 'Nama Group', 'status' => 'Status']);

                if ($validator->fails()) {
                    $this->addFailure($rowNumber, $validator->errors()->all());

                    continue;
                }

                $client = filled($data['client_code']) ? $clients->get(mb_strtolower($data['client_code'])) : $clientsById->get($this->currentClientId);
                if (! $client) {
                    $this->addFailure($rowNumber, [sprintf('Client dengan kode "%s" tidak ditemukan atau tidak dapat diakses.', $data['client_code'] ?: $this->currentClientId)]);

                    continue;
                }

                $key = $client->id.'|'.mb_strtolower($data['code']);
                if (isset($seen[$key])) {
                    $this->addFailure($rowNumber, [sprintf('Kode group "%s" duplikat pada file import (baris %d).', $data['code'], $seen[$key])]);

                    continue;
                }
                $seen[$key] = $rowNumber;

                app(CurrentClientService::class)->runAs($client, function () use ($data, $client, $groups, $key): void {
                    $group = $groups[$key] ?? null;
                    if ($group) {
                        $group->update(['name' => $data['name'], 'status' => $data['status'] ?: 'active']);

                        return;
                    }

                    Group::query()->create([
                        'client_id' => $client->id,
                        'code' => $data['code'],
                        'name' => $data['name'],
                        'status' => $data['status'] ?: 'active',
                    ]);
                });

                $this->imported++;
            }
        });
    }

    /** @param array<string, mixed> $data */
    private function normalizeRow(array $data): array
    {
        return [
            'client_code' => $this->normalizeCode($data['client_code'] ?? $data['kode_client'] ?? null),
            'code' => $this->normalizeCode($data['code'] ?? $data['group_code'] ?? $data['kode_group'] ?? null),
            'name' => trim((string) ($data['name'] ?? $data['nama_group'] ?? '')),
            'status' => trim((string) ($data['status'] ?? '')) ?: null,
        ];
    }

    private function normalizeCode(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** @param array<int, string> $errors */
    private function addFailure(int $row, array $errors): void
    {
        $this->failures[] = ['row' => $row, 'errors' => $errors];
    }
}
