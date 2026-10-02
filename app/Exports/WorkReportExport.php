<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkReportExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    private int $rowNumber = 0;

    public function __construct(
        private readonly int $clientId,
        private readonly ?string $dateFrom = null,
        private readonly ?string $dateTo = null,
        private readonly ?int $employeeUserId = null,
    ) {}

    public function query(): Builder
    {
        $query = DB::table('work_realizations as realization')
            ->join('realization_employees as assignment', function ($join): void {
                $join->on('assignment.work_realization_id', '=', 'realization.id')
                    ->on('assignment.client_id', '=', 'realization.client_id');
            })
            ->join('employees', function ($join): void {
                $join->on('employees.id', '=', 'assignment.employee_id')
                    ->on('employees.client_id', '=', 'realization.client_id');
            })
            ->join('shifts', function ($join): void {
                $join->on('shifts.id', '=', 'realization.shift_id')
                    ->on('shifts.client_id', '=', 'realization.client_id');
            })
            ->join('products', function ($join): void {
                $join->on('products.id', '=', 'realization.product_id')
                    ->on('products.client_id', '=', 'realization.client_id');
            })
            ->where('realization.client_id', $this->clientId)
            ->select([
                'realization.id as realization_id',
                'realization.work_date',
                'employees.employee_no',
                DB::raw('COALESCE(employees.sim_id, employees.employee_no) AS sim_id'),
                'employees.full_name',
                'shifts.name as shift_name',
                'realization.sku_snapshot',
                'realization.product_name_snapshot as product_name',
                'realization.total_output as actual',
                'realization.start_time',
                'realization.end_time',
                'products.estimated_output_per_hour',
                'products.po_price',
                DB::raw("COALESCE(realization.report, '') AS description"),
            ])
            ->orderByDesc('realization.work_date')
            ->orderByDesc('realization.id')
            ->orderBy('assignment.id');

        if ($this->dateFrom !== null) {
            $query->where('realization.work_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== null) {
            $query->where('realization.work_date', '<=', $this->dateTo);
        }

        if ($this->employeeUserId !== null) {
            $query->where('employees.user_id', $this->employeeUserId);
        }

        return $query;
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            'No', 'Tanggal pekerjaan', 'ID Karyawan', 'SIM ID', 'Nama lengkap', 'Shift', 'SKU',
            'Produk', 'Target', 'Harga target', 'Aktual', 'Harga aktual', 'Status tercapai', 'Deskripsi',
        ];
    }

    /** @return array<int, mixed> */
    public function map(mixed $row): array
    {
        $target = $this->targetOutput($row);
        $status = $this->achievementStatus($target, $row->actual);

        return [
            ++$this->rowNumber,
            $row->work_date ? date('d/m/Y', strtotime($row->work_date)) : '—',
            $row->employee_no,
            $row->sim_id ?: '—',
            $row->full_name,
            $row->shift_name,
            $row->sku_snapshot ?: '—',
            $row->product_name ?: '—',
            $target,
            $target === null || $row->po_price === null ? null : round($target * (float) $row->po_price),
            $row->actual === null ? null : (float) $row->actual,
            $row->actual === null || $row->po_price === null ? null : round((float) $row->actual * (float) $row->po_price),
            $status,
            $row->description ?: '—',
        ];
    }

    /** @return array<int, mixed> */
    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:N1');
        $sheet->getStyle('A1:N1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $currencyFormat = '[$Rp-421] #,##0;[Red]-[$Rp-421] #,##0';
        $sheet->getStyle('J2:J'.$sheet->getHighestRow())->getNumberFormat()->setFormatCode($currencyFormat);
        $sheet->getStyle('L2:L'.$sheet->getHighestRow())->getNumberFormat()->setFormatCode($currencyFormat);

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $status = $sheet->getCell("M{$row}")->getValue();
            $style = match ($status) {
                'Tercapai' => ['background' => 'D1FAE5', 'foreground' => '047857'],
                'Belum tercapai' => ['background' => 'FFE4E6', 'foreground' => 'BE123C'],
                default => ['background' => 'F1F5F9', 'foreground' => '475569'],
            };

            $sheet->getStyle("M{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($style['background']);
            $sheet->getStyle("M{$row}")->getFont()->getColor()->setRGB($style['foreground']);
            $sheet->getStyle("M{$row}")->getFont()->setBold(true);
        }

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ],
        ];
    }

    private function targetOutput(object $row): ?float
    {
        if ($row->estimated_output_per_hour === null || $row->start_time === null || $row->end_time === null) {
            return null;
        }

        $start = Carbon::parse((string) $row->start_time);
        $end = Carbon::parse((string) $row->end_time);
        $minutes = $start->diffInMinutes($end, false);

        if ($minutes < 0) {
            $minutes += 24 * 60;
        }

        return (float) $row->estimated_output_per_hour * ($minutes / 60);
    }

    private function achievementStatus(?float $target, mixed $actual): string
    {
        if ($target === null || $actual === null || $actual === '') {
            return 'Belum dapat dinilai';
        }

        return (float) $actual >= $target ? 'Tercapai' : 'Belum tercapai';
    }
}
