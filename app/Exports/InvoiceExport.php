<?php

namespace App\Exports;

use App\Services\InvoiceBoronganQuery;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoiceExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    private int $rowNumber = 0;

    public function __construct(
        private readonly int $clientId,
        private readonly ?string $period = null,
        private readonly ?int $shiftId = null,
        private readonly ?int $costCenterId = null,
        private readonly ?string $sku = null,
        private readonly ?string $batchNo = null,
    ) {}

    public function query(): Builder
    {
        return app(InvoiceBoronganQuery::class)->build(
            $this->clientId,
            $this->period,
            $this->shiftId,
            $this->costCenterId,
            $this->sku,
            $this->batchNo,
        );
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['No', 'Invoice', 'Periode', 'Shift', 'SKU', 'Cost Center', 'Batch', 'Qty', 'Satuan', 'Man Power', 'Harga PO', 'Amount PO'];
    }

    /** @return array<int, mixed> */
    public function map(mixed $row): array
    {
        return [
            ++$this->rowNumber,
            $row->invoice,
            $row->period ? date('m/Y', strtotime($row->period.'-01')) : '—',
            $row->shift ?: '—',
            $row->sku ?: '—',
            $row->cost_center ?: '—',
            $row->batch ?: '—',
            $row->qty === null ? null : (float) $row->qty,
            $row->unit ?: '—',
            (int) $row->manpower,
            (int) ($row->po_price ?? 0),
            (int) ($row->amount_po ?? 0),
        ];
    }

    /** @return array<int, mixed> */
    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:L1');
        $sheet->getStyle('A1:L1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ],
        ];
    }
}
