<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\DeductionPeriod;
use App\Models\Employee;
use App\Models\EmployeeDeduction;
use App\Models\Scopes\ClientScope;
use App\Services\CurrentClientService;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class DeductionImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    /** @var array<int, array{row: int, errors: array<int, string>}> */
    public array $failures = [];

    public int $imported = 0;

    /** @param list<int> $allowedClientIds */
    public function __construct(
        private readonly int $currentClientId,
        private readonly array $allowedClientIds,
        private readonly int $userId,
    ) {}

    public function collection(SupportCollection $rows): void
    {
        DB::transaction(function () use ($rows): void {
            /** @var array<string, DeductionPeriod> $periodCache */
            $periodCache = [];
            $clients = Client::query()
                ->active()
                ->whereIn('id', $this->allowedClientIds)
                ->get()
                ->keyBy(fn (Client $client): string => mb_strtolower($client->code));
            $clientsById = $clients->keyBy('id');
            $employeeKeys = $rows
                ->map(fn ($row): string => trim((string) ($row['no_karyawan'] ?? $row['sim_id'] ?? '')))
                ->filter()
                ->unique()
                ->values();
            $employees = Employee::query()
                ->withoutGlobalScope(ClientScope::class)
                ->whereIn('client_id', $this->allowedClientIds)
                ->where('status', 'active')
                ->where(function ($query) use ($employeeKeys): void {
                    $query->whereIn('employee_no', $employeeKeys)->orWhereIn('sim_id', $employeeKeys);
                })
                ->get();
            /** @var array<string, Employee> $employeeCache */
            $employeeCache = [];
            foreach ($employees as $employee) {
                if ($employee->employee_no) {
                    $employeeCache[$employee->client_id.'|'.mb_strtolower($employee->employee_no)] = $employee;
                }
                if ($employee->sim_id) {
                    $employeeCache[$employee->client_id.'|'.mb_strtolower($employee->sim_id)] = $employee;
                }
            }
            $seenDeductionKeys = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // Row 1 is the heading row.
                $data = $this->normalizeTableRow($row->toArray());
                $data['bulan'] = $this->normalizeMonth($data['bulan'] ?? null);

                $validator = Validator::make($data, [
                    'bulan' => ['required', 'date_format:Y-m'],
                    'minggu' => ['nullable', 'integer', 'between:1,2'],
                    'no_karyawan' => ['required', 'string'],
                    'potongan_seragam' => ['nullable', 'numeric', 'min:0'],
                    'potongan_perlengkapan' => ['nullable', 'numeric', 'min:0'],
                    'potongan_uang_makan' => ['nullable', 'numeric', 'min:0'],
                    'bpjs_kesehatan_persen' => ['nullable', 'numeric', 'between:0,100'],
                    'bpjs_ketenagakerjaan_persen' => ['nullable', 'numeric', 'between:0,100'],
                    'tipe_dp_gaji' => ['nullable', Rule::in(['fixed', 'percentage'])],
                    'nilai_dp_gaji' => ['nullable', 'numeric', 'min:0'],
                    'koreksi_pengurangan' => ['nullable', 'numeric', 'min:0'],
                    'koreksi_penambahan' => ['nullable', 'numeric', 'min:0'],
                ], [], [
                    'bulan' => 'Bulan', 'minggu' => 'Minggu', 'no_karyawan' => 'No Karyawan',
                    'potongan_seragam' => 'Potongan Seragam', 'potongan_perlengkapan' => 'Potongan Perlengkapan',
                    'potongan_uang_makan' => 'Potongan Uang Makan', 'bpjs_kesehatan_persen' => 'BPJS Kesehatan Persen',
                    'bpjs_ketenagakerjaan_persen' => 'BPJS Ketenagakerjaan Persen', 'tipe_dp_gaji' => 'Tipe DP Gaji',
                    'nilai_dp_gaji' => 'Nilai DP Gaji', 'koreksi_pengurangan' => 'Koreksi Pengurangan', 'koreksi_penambahan' => 'Koreksi Penambahan',
                ]);

                if ($validator->fails()) {
                    $this->failures[] = ['row' => $rowNumber, 'errors' => $validator->errors()->all()];

                    continue;
                }

                $employeeNo = trim((string) $data['no_karyawan']);
                $client = filled($data['client_code'])
                    ? $clients->get(mb_strtolower($data['client_code']))
                    : $clientsById->get($this->currentClientId);
                if (! $client) {
                    $this->failures[] = ['row' => $rowNumber, 'errors' => [sprintf('Client dengan kode "%s" tidak ditemukan atau tidak dapat diakses.', $data['client_code'] ?: $this->currentClientId)]];

                    continue;
                }

                $employee = $employeeCache[$client->id.'|'.mb_strtolower($employeeNo)] ?? null;
                if (! $employee) {
                    $this->failures[] = ['row' => $rowNumber, 'errors' => ["No Karyawan \"{$employeeNo}\" tidak ditemukan."]];

                    continue;
                }

                $namaLengkap = trim((string) ($data['nama_lengkap'] ?? ''));
                if ($namaLengkap !== '' && mb_strtolower($namaLengkap) !== mb_strtolower($employee->full_name)) {
                    $this->failures[] = ['row' => $rowNumber, 'errors' => ["Nama Lengkap tidak cocok dengan No Karyawan {$employeeNo} ({$employee->full_name})."]];

                    continue;
                }

                $weekNo = filled($data['minggu'] ?? null) ? (int) $data['minggu'] : null;
                $deductionKey = $client->id.'|'.$data['bulan'].'|'.($weekNo ?? 0).'|'.$employee->id;
                if (isset($seenDeductionKeys[$deductionKey])) {
                    $this->failures[] = ['row' => $rowNumber, 'errors' => [sprintf('Kombinasi client, bulan, minggu, dan karyawan duplikat pada file import (baris %d).', $seenDeductionKeys[$deductionKey])]];

                    continue;
                }
                $seenDeductionKeys[$deductionKey] = $rowNumber;

                $cacheKey = $client->id.'|'.$data['bulan'].'|'.($weekNo ?? 0);
                if (! isset($periodCache[$cacheKey])) {
                    $periodCache[$cacheKey] = app(CurrentClientService::class)->runAs($client, fn (): DeductionPeriod => DeductionPeriod::query()->firstOrCreate(
                        ['client_id' => $client->id, 'month' => $data['bulan'].'-01', 'week_no' => $weekNo],
                        ['status' => 'draft', 'created_by' => $this->userId],
                    ));
                }

                $alreadyExists = app(CurrentClientService::class)->runAs($client, fn (): bool => EmployeeDeduction::query()
                    ->where('deduction_period_id', $periodCache[$cacheKey]->getKey())
                    ->where('employee_id', $employee->id)
                    ->exists());
                if ($alreadyExists) {
                    $this->failures[] = ['row' => $rowNumber, 'errors' => ['Karyawan sudah memiliki potongan pada kombinasi client, bulan, dan minggu tersebut.']];

                    continue;
                }

                app(CurrentClientService::class)->runAs($client, function () use ($data, $employee, $periodCache, $cacheKey, $client): void {
                    $periodCache[$cacheKey]->deductions()->create([
                        'client_id' => $client->id,
                        'employee_id' => $employee->id,
                        'uniform_amount' => (int) round((float) ($data['potongan_seragam'] ?? 0)),
                        'equipment_amount' => (int) round((float) ($data['potongan_perlengkapan'] ?? 0)),
                        'meal_amount' => (int) round((float) ($data['potongan_uang_makan'] ?? 0)),
                        'bpjs_health_percent' => (float) ($data['bpjs_kesehatan_persen'] ?? 0),
                        'bpjs_employment_percent' => (float) ($data['bpjs_ketenagakerjaan_persen'] ?? 0),
                        'salary_advance_type' => filled($data['tipe_dp_gaji'] ?? null) ? $data['tipe_dp_gaji'] : null,
                        'salary_advance_value' => (float) ($data['nilai_dp_gaji'] ?? 0),
                        'correction_minus' => (int) round((float) ($data['koreksi_pengurangan'] ?? 0)),
                        'correction_plus' => (int) round((float) ($data['koreksi_penambahan'] ?? 0)),
                        'notes' => filled($data['catatan'] ?? null) ? $data['catatan'] : null,
                    ]);
                });

                $this->imported++;
            }
        });
    }

    private function normalizeMonth(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m');
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m');
        }

        return trim((string) $value);
    }

    private function normalizeCode(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Convert the visible list-table format into the detailed import shape.
     * The detailed format remains supported for existing files.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeTableRow(array $data): array
    {
        $clientCode = $this->normalizeCode($data['client_code'] ?? $data['kode_client'] ?? $data['kode_klien'] ?? null);
        if (! array_key_exists('periode', $data) && ! array_key_exists('sim_id', $data)) {
            return [
                ...$data,
                'client_code' => $clientCode,
                'no_karyawan' => $this->normalizeCode($data['no_karyawan'] ?? $data['employee_no'] ?? null),
                'potongan_perlengkapan' => $data['potongan_perlengkapan'] ?? $data['potongan_perlengkapan_kerja'] ?? null,
            ];
        }

        $period = $this->parseTablePeriod($data['periode'] ?? null);

        return [
            ...$data,
            'client_code' => $clientCode,
            'bulan' => $period['month'] ?? '',
            'minggu' => $period['week'],
            'no_karyawan' => trim((string) ($data['sim_id'] ?? '')),
            'potongan_perlengkapan' => $data['potongan_perlengkapan'] ?? $data['potongan_perlengkapan_kerja'] ?? 0,
            'bpjs_kesehatan_persen' => $this->normalizePercent($data['bpjs_kesehatan'] ?? null),
            'bpjs_ketenagakerjaan_persen' => $this->normalizePercent($data['bpjs_ketenagakerjaan'] ?? null),
            'potongan_seragam' => 0,
            'potongan_perlengkapan' => 0,
            'potongan_uang_makan' => 0,
            'tipe_dp_gaji' => null,
            'nilai_dp_gaji' => 0,
            'koreksi_pengurangan' => $this->normalizeAmount($data['koreksi_pengurangan'] ?? null),
            'koreksi_penambahan' => $this->normalizeAmount($data['koreksi_penambahan'] ?? null),
        ];
    }

    /**
     * @return array{month: ?string, week: ?int}
     */
    private function parseTablePeriod(mixed $value): array
    {
        $period = trim((string) $value);
        if ($period === '') {
            return ['month' => null, 'week' => null];
        }

        preg_match('/^([[:alpha:]]+)\s+(\d{4})(?:\s*\(Minggu\s*([12])\))?$/iu', $period, $matches);
        $months = [
            'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4, 'mei' => 5, 'juni' => 6,
            'juli' => 7, 'agustus' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
        ];
        $month = $months[mb_strtolower($matches[1] ?? '')] ?? null;

        return [
            'month' => $month ? sprintf('%04d-%02d', (int) ($matches[2] ?? 0), $month) : null,
            'week' => isset($matches[3]) && $matches[3] !== '' ? (int) $matches[3] : null,
        ];
    }

    private function normalizePercent(mixed $value): float|int
    {
        $value = str_replace('%', '', trim((string) $value));
        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? (float) $value : 0;
    }

    private function normalizeAmount(mixed $value): int
    {
        $value = preg_replace('/[^\d-]/', '', (string) $value) ?? '';

        return $value === '' || $value === '-' ? 0 : (int) $value;
    }
}
