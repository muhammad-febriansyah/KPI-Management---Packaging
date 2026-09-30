<?php

namespace App\Imports;

use App\Models\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ClientImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    /** @var array<int, array{row: int, errors: array<int, string>}> */
    public array $failures = [];

    public int $imported = 0;

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows): void {
            $clients = Client::withTrashed()->get()->keyBy(fn (Client $client): string => mb_strtolower($client->code));
            $seen = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $data = $this->normalizeRow($row->toArray());
                $validator = Validator::make($data, [
                    'code' => ['required', 'string', 'max:50'],
                    'name' => ['required', 'string', 'max:150'],
                    'timezone' => ['nullable', 'string', 'max:50'],
                    'status' => ['required', Rule::in(['active', 'inactive'])],
                ], [], ['code' => 'Client Code', 'name' => 'Nama Client', 'timezone' => 'Timezone', 'status' => 'Status']);

                if ($validator->fails()) {
                    $this->addFailure($rowNumber, $validator->errors()->all());

                    continue;
                }

                $key = mb_strtolower($data['code']);
                if (isset($seen[$key])) {
                    $this->addFailure($rowNumber, [sprintf('Client Code "%s" duplikat pada file import (baris %d).', $data['code'], $seen[$key])]);

                    continue;
                }
                $seen[$key] = $rowNumber;

                $client = $clients->get($key);
                if ($client?->trashed()) {
                    $client->restore();
                }

                $client ??= new Client;
                $client->fill([
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'timezone' => $data['timezone'] ?: 'Asia/Jakarta',
                    'status' => $data['status'],
                ]);
                $client->save();
                $this->imported++;
            }
        });
    }

    /** @param array<string, mixed> $data */
    private function normalizeRow(array $data): array
    {
        return [
            'code' => $this->normalizeCode($data['code'] ?? $data['client_code'] ?? $data['kode_client'] ?? null),
            'name' => trim((string) ($data['name'] ?? $data['nama_client'] ?? $data['nama'] ?? '')),
            'timezone' => $this->normalizeCode($data['timezone'] ?? null),
            'status' => trim((string) ($data['status'] ?? '')),
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
