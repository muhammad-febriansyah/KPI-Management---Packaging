<?php

namespace App\Exports;

use App\Services\PayrollReportBuilder;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PayrollReportExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    private int $rowNumber = 0;

    public function __construct(
        private readonly int $clientId,
        private readonly string $dateFrom,
        private readonly string $dateTo,
    ) {}

    public function query(): Builder
    {
        return (new PayrollReportBuilder)->forClient($this->clientId, $this->dateFrom, $this->dateTo);
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            'No',
            'NIK',
            'Nama Lengkap',
            'Jenis Kelamin',
            'Total Hari Masuk',
            'Gaji Bersih',
            'Gaji Kotor',
            'BPJS Kesehatan',
            'BPJS Ketenagakerjaan',
            'Seragam (Kaos/Celana)',
            'Perlengkapan Kerja',
            'Uang Makan',
            'DP Gaji',
            'Koreksi Pengurangan',
            'Koreksi Penambahan',
        ];
    }

    /** @return array<int, mixed> */
    public function map($row): array
    {
        return [
            ++$this->rowNumber,
            $row->employee_no,
            $row->full_name,
            $row->gender === 'male' ? 'Laki-laki' : 'Perempuan',
            (int) $row->attendance_days,
            PayrollReportBuilder::netSalary($row),
            (int) $row->gross_salary,
            PayrollReportBuilder::bpjsHealth($row),
            PayrollReportBuilder::bpjsEmployment($row),
            (float) $row->uniform_amount,
            (float) $row->equipment_amount,
            (float) $row->meal_amount,
            PayrollReportBuilder::salaryAdvanceAmount($row),
            (float) $row->correction_minus,
            (float) $row->correction_plus,
        ];
    }

    /** @return array<int, mixed> */
    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:O1');
        $sheet->getStyle('A1:O1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ],
        ];
    }
}
