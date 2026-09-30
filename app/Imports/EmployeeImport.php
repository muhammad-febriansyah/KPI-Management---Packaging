<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Group;
use App\Models\Role;
use App\Models\Scopes\ClientScope;
use App\Models\User;
use App\Services\CurrentClientService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class EmployeeImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
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
            $employees = Employee::query()->withoutGlobalScope(ClientScope::class)->whereIn('client_id', $this->allowedClientIds)->whereNotNull('sim_id')->get()->mapWithKeys(fn (Employee $employee): array => [$employee->client_id.'|'.mb_strtolower($employee->sim_id) => $employee])->all();
            $seenSimIds = [];
            $seenEmails = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $data = $this->normalizeRow($row->toArray());
                $validator = Validator::make($data, [
                    'client_code' => ['nullable', 'string', 'max:50'], 'sim_id' => ['nullable', 'string', 'max:100'],
                    'full_name' => ['required', 'string', 'max:150'], 'email' => ['nullable', 'email', 'max:150'],
                    'phone' => ['required', 'string', 'max:30'], 'join_date' => ['required', 'date'], 'birth_date' => ['required', 'date', 'before:today'],
                    'gender' => ['required', Rule::in(['male', 'female'])], 'employee_status' => ['required', Rule::in(['permanent', 'contract', 'daily'])],
                    'marital_status' => ['required', Rule::in(['single', 'married', 'divorced', 'widowed'])],
                    'group_code' => ['nullable', 'string', 'max:50'], 'status' => ['nullable', Rule::in(['active', 'inactive'])],
                ], [], [
                    'client_code' => 'Client Code', 'sim_id' => 'SIM ID', 'full_name' => 'Nama Lengkap', 'email' => 'Email',
                    'phone' => 'Nomor Telepon', 'join_date' => 'Tanggal Masuk', 'birth_date' => 'Tanggal Lahir', 'gender' => 'Jenis Kelamin',
                    'employee_status' => 'Status Karyawan', 'marital_status' => 'Status Perkawinan', 'group_code' => 'Group Code', 'status' => 'Status',
                ]);

                if ($validator->fails()) {
                    $this->addFailure($rowNumber, $validator->errors()->all());

                    continue;
                }

                $client = filled($data['client_code']) ? $clients->get(mb_strtolower($data['client_code'])) : $clientsById->get($this->currentClientId);
                if (! $client) {
                    $this->addFailure($rowNumber, [sprintf('Client dengan kode "%s" tidak ditemukan atau tidak dapat diakses.', $data['client_code'] ?: $this->currentClientId)]);

                    continue;
                }

                $simKey = filled($data['sim_id']) ? $client->id.'|'.mb_strtolower($data['sim_id']) : null;
                if ($simKey && isset($seenSimIds[$simKey])) {
                    $this->addFailure($rowNumber, [sprintf('SIM ID "%s" duplikat pada file import (baris %d).', $data['sim_id'], $seenSimIds[$simKey])]);

                    continue;
                }
                if ($simKey) {
                    $seenSimIds[$simKey] = $rowNumber;
                }

                $employee = $simKey ? ($employees[$simKey] ?? null) : null;
                $emailKey = filled($data['email']) ? mb_strtolower($data['email']) : null;
                if ($emailKey && isset($seenEmails[$emailKey])) {
                    $this->addFailure($rowNumber, [sprintf('Email "%s" duplikat pada file import (baris %d).', $data['email'], $seenEmails[$emailKey])]);

                    continue;
                }
                if ($emailKey) {
                    $seenEmails[$emailKey] = $rowNumber;
                }

                $emailValidator = Validator::make(['email' => $data['email']], ['email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($employee?->user_id)]], [], ['email' => 'Email']);
                if ($emailValidator->fails()) {
                    $this->addFailure($rowNumber, $emailValidator->errors()->all());

                    continue;
                }

                $group = null;
                if (filled($data['group_code'])) {
                    $group = $groups[$client->id.'|'.mb_strtolower($data['group_code'])] ?? null;
                    if (! $group) {
                        $this->addFailure($rowNumber, [sprintf('Group dengan kode "%s" tidak ditemukan pada client %s.', $data['group_code'], $client->code)]);

                        continue;
                    }
                }

                $payload = [
                    'client_id' => $client->id, 'sim_id' => $data['sim_id'], 'full_name' => $data['full_name'],
                    'email' => $data['email'], 'phone' => $data['phone'], 'join_date' => $data['join_date'], 'birth_date' => $data['birth_date'],
                    'gender' => $data['gender'], 'employee_status' => $data['employee_status'], 'marital_status' => $data['marital_status'],
                    'group_id' => $group?->id, 'status' => $data['status'] ?: 'active',
                ];

                app(CurrentClientService::class)->runAs($client, function () use ($employee, $payload, $client): void {
                    if (! $employee) {
                        $employee = Employee::query()->create($payload + ['employee_no' => $this->generateEmployeeNumber($client->id), 'rate_category' => 'baru']);
                    } else {
                        $employee->update($payload);
                    }

                    $this->syncEmployeeUser($employee, $client->id);
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
            'sim_id' => $this->normalizeCode($data['sim_id'] ?? null),
            'full_name' => trim((string) ($data['full_name'] ?? $data['nama_lengkap'] ?? '')),
            'email' => $this->normalizeCode($data['email'] ?? null),
            'phone' => trim((string) ($data['phone'] ?? $data['nomor_telepon'] ?? '')),
            'join_date' => $this->normalizeDate($data['join_date'] ?? $data['tanggal_masuk'] ?? null),
            'birth_date' => $this->normalizeDate($data['birth_date'] ?? $data['tanggal_lahir'] ?? null),
            'gender' => trim((string) ($data['gender'] ?? $data['jenis_kelamin'] ?? '')),
            'employee_status' => trim((string) ($data['employee_status'] ?? $data['status_karyawan'] ?? '')),
            'marital_status' => trim((string) ($data['marital_status'] ?? $data['status_perkawinan'] ?? '')),
            'group_code' => $this->normalizeCode($data['group_code'] ?? $data['kode_group'] ?? null),
            'status' => trim((string) ($data['status'] ?? '')) ?: null,
        ];
    }

    private function normalizeCode(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        return $this->normalizeCode($value);
    }

    private function generateEmployeeNumber(int $clientId): string
    {
        do {
            $employeeNumber = (string) random_int(100000000, 999999999);
        } while (Employee::query()->where('client_id', $clientId)->where('employee_no', $employeeNumber)->exists() || User::query()->where('username', $employeeNumber)->exists());

        return $employeeNumber;
    }

    private function syncEmployeeUser(Employee $employee, int $clientId): void
    {
        $user = $employee->user ?? new User;
        $isNewUser = ! $user->exists;
        $user->fill(['name' => $employee->full_name, 'email' => $employee->email, 'username' => $user->username ?? $employee->employee_no, 'status' => $employee->status]);
        if ($isNewUser) {
            $user->password = $employee->birth_date ? date('dmY', strtotime($employee->birth_date)) : 'password';
        }
        $user->save();
        $roleId = Role::query()->firstOrCreate(['code' => 'employee'], ['name' => 'Karyawan'])->getKey();
        $user->clients()->syncWithoutDetaching([$clientId => ['role_id' => $roleId, 'is_default' => true, 'status' => $employee->status]]);
        $employee->update(['user_id' => $user->getKey()]);
    }

    /** @param array<int, string> $errors */
    private function addFailure(int $row, array $errors): void
    {
        $this->failures[] = ['row' => $row, 'errors' => $errors];
    }
}
