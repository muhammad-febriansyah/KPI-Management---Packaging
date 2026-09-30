<?php

namespace App\Exports;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeExport implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithMapping, WithStyles
{
    private int $rowNumber = 0;

    public function __construct(private readonly int $clientId, private readonly string $clientLabel) {}

    public function query(): Builder
    {
        return Employee::query()
            ->where('client_id', $this->clientId)
            ->with('group')
            ->orderBy('full_name');
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['No', 'ID Karyawan', 'SIM ID', 'Nama Lengkap', 'Email', 'Nomor Telepon', 'Tanggal Masuk', 'Tanggal Lahir', 'Jenis Kelamin', 'Status Karyawan', 'Status Perkawinan', 'Group', 'Status'];
    }

    /** @return array<int, mixed> */
    public function map($employee): array
    {
        return [
            ++$this->rowNumber,
            $employee->employee_no,
            $employee->sim_id ?: '—',
            $employee->full_name,
            $employee->email ?: '—',
            $employee->phone,
            $employee->join_date ? date('d/m/Y', strtotime($employee->join_date)) : '—',
            $employee->birth_date ? date('d/m/Y', strtotime($employee->birth_date)) : '—',
            match ($employee->gender) {
                'male' => 'Laki-laki', 'female' => 'Perempuan', default => '—',
            },
            match ($employee->employee_status) {
                'permanent' => 'Tetap', 'contract' => 'Kontrak', 'daily' => 'Harian', default => '—',
            },
            match ($employee->marital_status) {
                'single' => 'Belum menikah', 'married' => 'Menikah', 'divorced' => 'Cerai hidup', 'widowed' => 'Cerai mati', default => '—',
            },
            $employee->group?->name ?: 'Tanpa group',
            $employee->status === 'active' ? 'Aktif' : 'Nonaktif',
        ];
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_TEXT,
            'F' => NumberFormat::FORMAT_TEXT,
            'G' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'H' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }

    /** @return array<int, mixed> */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]],
        ];
    }

    /** @return array<class-string, callable> */
    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $sheet = $event->sheet->getDelegate();
            $sheet->insertNewRowBefore(1, 4);
            $sheet->mergeCells('A1:M1');
            $sheet->mergeCells('A2:M2');
            $sheet->mergeCells('A3:M3');
            $sheet->mergeCells('A4:M4');
            $sheet->setCellValue('A1', 'LAPORAN MASTER KARYAWAN');
            $sheet->setCellValue('A2', 'Client: '.$this->clientLabel);
            $sheet->setCellValue('A3', 'Tanggal cetak: '.now()->format('d/m/Y H:i'));
            $sheet->setCellValue('A4', 'Data diurutkan berdasarkan nama lengkap');
            $sheet->getStyle('A1:M1')->applyFromArray(['font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]]);
            $sheet->getStyle('A2:M3')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => '334155']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]]);
            $sheet->getStyle('A4:M4')->applyFromArray(['font' => ['italic' => true, 'color' => ['rgb' => '64748B']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]]);
            $sheet->getStyle('A5:M5')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true], 'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D8E1F0']]]]);
            $sheet->getStyle('A6:M'.max(6, $sheet->getHighestRow()))->getBorders()->getBottom()->setBorderStyle(Border::BORDER_HAIR);
            $sheet->setAutoFilter('A5:M5');
            $sheet->freezePane('A6');
            $sheet->getRowDimension(1)->setRowHeight(28);
            $sheet->getRowDimension(5)->setRowHeight(32);
        }];
    }
}
