<?php

namespace App\Exports;

use App\Models\Client;
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
    public function __construct(
        private readonly ?Client $exampleClient,
        private readonly ?Employee $exampleEmployee,
    ) {}

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        if (! $this->exampleEmployee) {
            return [];
        }

        return [[
            $this->exampleClient?->code, now()->format('Y-m'), 1,
            $this->exampleEmployee->sim_id ?: $this->exampleEmployee->employee_no,
            $this->exampleEmployee->full_name, 0, 0, 0, 0, 0, '', 0, 0, 0, '',
        ]];
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Kode Klien', 'Bulan', 'Minggu', 'No Karyawan', 'Nama Lengkap', 'Potongan Seragam', 'Potongan Perlengkapan Kerja', 'Potongan Uang Makan', 'BPJS Kesehatan Persen', 'BPJS Ketenagakerjaan Persen', 'Tipe DP Gaji', 'Nilai DP Gaji', 'Koreksi Pengurangan', 'Koreksi Penambahan', 'Catatan'];
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        return ['A' => NumberFormat::FORMAT_TEXT, 'B' => NumberFormat::FORMAT_TEXT, 'C' => NumberFormat::FORMAT_TEXT, 'D' => NumberFormat::FORMAT_TEXT];
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
            'A1' => 'Wajib diisi dengan kode client. Kosong memakai client aktif saat import.',
            'B1' => 'Format bulan: YYYY-MM. Contoh: 2026-09.',
            'C1' => 'Kosongkan untuk semua minggu. Isi 1 atau 2.',
            'D1' => 'Wajib diisi dengan No Karyawan atau SIM ID pada client terkait.',
            'E1' => 'Opsional, dicocokkan otomatis dengan data karyawan.',
            'F1' => 'Nominal rupiah, dapat ditulis seperti 25000 atau Rp 25.000.',
            'G1' => 'Nominal rupiah, dapat ditulis seperti 25000 atau Rp 25.000.',
            'H1' => 'Nominal rupiah, dapat ditulis seperti 25000 atau Rp 25.000.',
            'I1' => 'Persen. Contoh: 1 atau 1%.',
            'J1' => 'Persen. Contoh: 2 atau 2%.',
            'K1' => 'Kosongkan, fixed, atau percentage.',
            'L1' => 'Nilai DP gaji sesuai tipe pada kolom sebelumnya.',
            'M1' => 'Nominal rupiah, dapat ditulis seperti 25000 atau Rp 25.000.',
            'N1' => 'Nominal rupiah, dapat ditulis seperti 25000 atau Rp 25.000.',
            'O1' => 'Catatan tambahan (opsional).',
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
