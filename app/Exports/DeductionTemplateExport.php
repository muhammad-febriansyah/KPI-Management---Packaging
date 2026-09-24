<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DeductionTemplateExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithStyles
{
    public function __construct(private readonly ?Employee $exampleEmployee) {}

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        if (! $this->exampleEmployee) {
            return [];
        }

        return [[
            now()->format('d/m/Y'), now()->locale('id')->translatedFormat('F Y'),
            $this->exampleEmployee->sim_id ?: $this->exampleEmployee->employee_no,
            $this->exampleEmployee->full_name, 0, 0, 0, 0,
        ]];
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Tanggal Input', 'Periode', 'SIM ID', 'Nama Lengkap', 'BPJS Kesehatan', 'BPJS Ketenagakerjaan', 'Koreksi Pengurangan', 'Koreksi Penambahan'];
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        return ['A' => NumberFormat::FORMAT_TEXT, 'B' => NumberFormat::FORMAT_TEXT, 'C' => NumberFormat::FORMAT_TEXT];
    }

    /** @return array<int, mixed> */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']]],
            2 => ['font' => ['italic' => true, 'color' => ['rgb' => '94A3B8']]],
        ];
    }

    /** @return array<class-string, callable> */
    public function registerEvents(): array
    {
        $notes = [
            'A1' => 'Tanggal input mengikuti format pada tabel. Kolom ini hanya informasi dan tidak dipakai untuk menentukan periode.',
            'B1' => 'Format: Nama bulan dan tahun, opsional diikuti (Minggu 1) atau (Minggu 2). Contoh: September 2026 (Minggu 1).',
            'C1' => 'Wajib diisi dengan SIM ID yang sama seperti Master Data > Karyawan.',
            'D1' => 'Hanya informasi, dicocokkan otomatis lewat SIM ID saat import.',
            'E1' => 'Persen. Contoh: 1 atau 1%.',
            'F1' => 'Persen. Contoh: 2 atau 2%.',
            'G1' => 'Nominal rupiah, dapat ditulis seperti 25000 atau Rp 25.000.',
            'H1' => 'Nominal rupiah, dapat ditulis seperti 25000 atau Rp 25.000.',
        ];

        return [
            AfterSheet::class => function (AfterSheet $event) use ($notes): void {
                $event->sheet->getDelegate()->freezePane('A2');
                foreach ($notes as $cell => $note) {
                    $event->sheet->getDelegate()->getComment($cell)->getText()->createTextRun($note);
                }
            },
        ];
    }
}
