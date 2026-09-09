<?php

namespace App\Exports;

use App\Models\EmployeeDeduction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DeductionExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private readonly int $clientId) {}

    public function collection(): Collection
    {
        return EmployeeDeduction::query()
            ->where('client_id', $this->clientId)
            ->with(['employee', 'deductionPeriod'])
            ->get()
            ->sortByDesc(fn (EmployeeDeduction $deduction): string => $deduction->deductionPeriod?->month?->format('Y-m').($deduction->deductionPeriod?->week_no ?? 0))
            ->values();
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Bulan', 'Minggu', 'No Karyawan', 'Nama Lengkap', 'Potongan Seragam', 'Potongan Perlengkapan', 'Potongan Uang Makan', 'BPJS Kesehatan Persen', 'BPJS Ketenagakerjaan Persen', 'Tipe DP Gaji', 'Nilai DP Gaji', 'Koreksi Pengurangan', 'Koreksi Penambahan', 'Catatan'];
    }

    /** @return array<int, mixed> */
    public function map($deduction): array
    {
        return [
            $deduction->deductionPeriod?->month?->format('Y-m'),
            $deduction->deductionPeriod?->week_no,
            $deduction->employee?->employee_no,
            $deduction->employee?->full_name,
            $deduction->uniform_amount,
            $deduction->equipment_amount,
            $deduction->meal_amount,
            (float) $deduction->bpjs_health_percent,
            (float) $deduction->bpjs_employment_percent,
            $deduction->salary_advance_type,
            (float) $deduction->salary_advance_value,
            $deduction->correction_minus,
            $deduction->correction_plus,
            $deduction->notes,
        ];
    }

    /** @return array<int, mixed> */
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']]]];
    }
}
