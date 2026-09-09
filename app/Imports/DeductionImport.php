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
            /** @var array<string, int> $periodCache */
            $periodCache = [];

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // Row 1 is the heading row.
                $data = $row->toArray();
                $data['bulan'] = $this->normalizeMonth($data['bulan'] ?? null);

                $validator = Validator::make($data, [
                    'bulan' => ['required', 'date_format:Y-m'],
                    'minggu' => ['nullable', 'integer', 'between:1,5'],
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
                $employee = Employee::query()->where('client_id', $this->clientId)->where('employee_no', $employeeNo)->first();
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
                    )->id;
                }

                DeductionPeriod::query()->find($periodCache[$cacheKey])->deductions()->updateOrCreate(
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
}
