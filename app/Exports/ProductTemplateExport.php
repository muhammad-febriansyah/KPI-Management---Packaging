<?php

namespace App\Exports;

use App\Models\Client;
use App\Models\CostCenter;
use App\Models\Group;
use App\Models\Unit;
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

class ProductTemplateExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithStyles
{
    public function __construct(
        private readonly Client $client,
        private readonly ?Unit $unit,
        private readonly ?Group $group,
        private readonly ?CostCenter $costCenter,
    ) {}

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        return [[
            $this->client->code,
            'SKU-CONTOH-001',
            'Nama Produk Contoh',
            $this->unit?->code ?? 'SATUAN-001',
            $this->group?->code ?? '',
            $this->costCenter?->code ?? '',
            0,
            0,
            0,
            'active',
        ]];
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Client Code', 'SKU', 'Nama Produk', 'Kode Satuan', 'Kode Group', 'Kode Cost Center', 'Harga PO', 'Tarif Karyawan', 'Output per Jam', 'Status'];
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        return ['A' => NumberFormat::FORMAT_TEXT, 'B' => NumberFormat::FORMAT_TEXT, 'D' => NumberFormat::FORMAT_TEXT, 'E' => NumberFormat::FORMAT_TEXT, 'F' => NumberFormat::FORMAT_TEXT];
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
            'A1' => 'Opsional untuk user client. Jika kosong, client aktif dipakai. Super Admin wajib mengisi untuk import lintas client.',
            'B1' => 'Wajib. SKU menjadi kunci unik per client; jika sudah ada, data produk diperbarui.',
            'C1' => 'Wajib diisi dengan nama produk.',
            'D1' => 'Wajib diisi dengan kode satuan aktif pada client terkait.',
            'E1' => 'Opsional. Isi dengan kode group aktif; kosongkan jika tanpa group.',
            'F1' => 'Opsional. Isi dengan kode cost center aktif; kosongkan jika tanpa cost center.',
            'G1' => 'Nominal. Contoh: 15000 atau 15.000,500.',
            'H1' => 'Nominal. Contoh: 550.750.',
            'I1' => 'Bilangan bulat. Contoh: 120.',
            'J1' => 'Isi active atau inactive.',
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
