<?php

namespace App\Imports;

use App\Models\DeductionPeriod;
use App\Models\Employee;
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

    public function __construct(private readonly int $clientId, private readonly int $userId) {}

    public function collection(SupportCollection $rows): void
    {
        DB::transaction(function () use ($rows): void {
            /** @var array<string, DeductionPeriod> $periodCache */
            $periodCache = [];
            $employeeKeys = $rows
                ->map(fn ($row): string => trim((string) ($row['no_karyawan'] ?? $row['sim_id'] ?? '')))
                ->filter()
                ->unique()
                ->values();
            $employees = Employee::query()
                ->where('client_id', $this->clientId)
                ->where(function ($query) use ($employeeKeys): void {
                    $query->whereIn('employee_no', $employeeKeys)->orWhereIn('sim_id', $employeeKeys);
                })
                ->get();
            /** @var array<string, Employee> $employeeCache */
            $employeeCache = [];
            foreach ($employees as $employee) {
                if ($employee->employee_no) {
                    $employeeCache[$employee->employee_no] = $employee;
                }
                if ($employee->sim_id) {
                    $employeeCache[$employee->sim_id] = $employee;
                }
            }

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
                $employee = $employeeCache[$employeeNo] ?? null;
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
                $cacheKey = $data['bulan'].'|'.($weekNo ?? 0);
                if (! isset($periodCache[$cacheKey])) {
                    $periodCache[$cacheKey] = DeductionPeriod::query()->firstOrCreate(
                        ['client_id' => $this->clientId, 'month' => $data['bulan'].'-01', 'week_no' => $weekNo],
                        ['status' => 'draft', 'created_by' => $this->userId],
                    );
                }

                $periodCache[$cacheKey]->deductions()->updateOrCreate(
                    ['employee_id' => $employee->id],
                    [
                        'client_id' => $this->clientId,
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
                    ],
                );

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

    /**
     * Convert the visible list-table format into the detailed import shape.
     * The detailed format remains supported for existing files.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeTableRow(array $data): array
    {
        if (! array_key_exists('periode', $data) && ! array_key_exists('sim_id', $data)) {
            return $data;
        }

        $period = $this->parseTablePeriod($data['periode'] ?? null);

        return [
            ...$data,
            'bulan' => $period['month'] ?? '',
            'minggu' => $period['week'],
            'no_karyawan' => trim((string) ($data['sim_id'] ?? '')),
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
