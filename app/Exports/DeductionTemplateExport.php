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
            now()->format('Y-m'), '', $this->exampleEmployee->employee_no, $this->exampleEmployee->full_name,
            0, 0, 0, 0, 0, '', 0, 0, 0,
            'Contoh baris — silakan ubah nilainya atau hapus baris ini sebelum import.',
        ]];
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Bulan', 'Minggu', 'No Karyawan', 'Nama Lengkap', 'Potongan Seragam', 'Potongan Perlengkapan', 'Potongan Uang Makan', 'BPJS Kesehatan Persen', 'BPJS Ketenagakerjaan Persen', 'Tipe DP Gaji', 'Nilai DP Gaji', 'Koreksi Pengurangan', 'Koreksi Penambahan', 'Catatan'];
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        return ['A' => NumberFormat::FORMAT_TEXT, 'C' => NumberFormat::FORMAT_TEXT];
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
            'A1' => 'Format: TAHUN-BULAN. Contoh: 2026-08.',
            'B1' => 'Angka 1-5. Kosongkan jika berlaku untuk semua minggu di bulan itu.',
            'C1' => 'Wajib diisi, harus sama persis dengan No. karyawan di Master Data > Karyawan.',
            'D1' => 'Hanya informasi, dicocokkan otomatis lewat No Karyawan saat import.',
            'E1' => 'Nominal rupiah, bilangan bulat.',
            'F1' => 'Nominal rupiah, bilangan bulat.',
            'G1' => 'Nominal rupiah, bilangan bulat.',
            'H1' => 'Persen. Contoh 1.5 untuk 1,5%. Kosongkan jika 0.',
            'I1' => 'Persen. Contoh 2 untuk 2%. Kosongkan jika 0.',
            'J1' => 'Isi salah satu: fixed atau percentage. Kosongkan jika tidak ada DP gaji.',
            'K1' => 'Nominal DP gaji, isi jika kolom Tipe DP Gaji diisi.',
            'L1' => 'Nominal rupiah, bilangan bulat.',
            'M1' => 'Nominal rupiah, bilangan bulat.',
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
