<?php

namespace App\Exports;

use App\Models\Client;
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

class GroupTemplateExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithStyles
{
    public function __construct(private readonly Client $client) {}

    /** @return array<int, array<int, mixed>> */
    public function array(): array
    {
        return [[$this->client->code, 'GROUP-CONTOH', 'Nama Group Contoh', 'active']];
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['Client Code', 'Group Code', 'Nama Group', 'Status'];
    }

    /** @return array<string, string> */
    public function columnFormats(): array
    {
        return ['A' => NumberFormat::FORMAT_TEXT, 'B' => NumberFormat::FORMAT_TEXT];
    }

    /** @return array<int, mixed> */
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']]], 2 => ['font' => ['italic' => true, 'color' => ['rgb' => '94A3B8']]]];
    }

    /** @return array<class-string, callable> */
    public function registerEvents(): array
    {
        return [AfterSheet::class => function (AfterSheet $event): void {
            $event->sheet->getDelegate()->freezePane('A2');
            $event->sheet->getDelegate()->getComment('A1')->getText()->createTextRun('Kosongkan untuk memakai client aktif.');
            $event->sheet->getDelegate()->getComment('B1')->getText()->createTextRun('Wajib dan menjadi kunci group per client.');
            $event->sheet->getDelegate()->getComment('C1')->getText()->createTextRun('Wajib.');
            $event->sheet->getDelegate()->getComment('D1')->getText()->createTextRun('active atau inactive.');
        }];
    }
}
