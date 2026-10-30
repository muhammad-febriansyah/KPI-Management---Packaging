<?php

namespace App\Exports;

use App\Services\InvoiceReportQuery;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoiceReportExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    private int $rowNumber = 0;

    public function __construct(
        private readonly int $clientId,
        private readonly ?string $periodFrom = null,
        private readonly ?string $periodTo = null,
    ) {}

    public function query(): Builder
    {
        return app(InvoiceReportQuery::class)->build($this->clientId, $this->periodFrom, $this->periodTo);
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['No', 'Periode', 'SKU', 'Cost Center', 'Qty', 'Satuan', 'Harga PO', 'Total PO'];
    }

    /** @return array<int, mixed> */
    public function map(mixed $row): array
    {
        return [
            ++$this->rowNumber,
            date('m-Y', strtotime($row->period.'-01')),
            $row->sku ?: '—',
            $row->cost_center ?: '—',
            (float) $row->qty,
            $row->unit ?: '—',
            (int) round((float) $row->po_price),
            (int) round((float) $row->total_po),
        ];
    }

    /** @return array<int, mixed> */
    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:H1');
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ],
        ];
    }
}
