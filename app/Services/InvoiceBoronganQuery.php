<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class InvoiceBoronganQuery
{
    public function build(
        int $clientId,
        ?string $period = null,
        ?int $shiftId = null,
        ?int $costCenterId = null,
        ?string $sku = null,
        ?string $batchNo = null,
    ): Builder {
        $assignmentCounts = DB::table('realization_employees as assignment')
            ->select([
                'assignment.client_id',
                'assignment.work_realization_id',
                DB::raw('COUNT(DISTINCT assignment.employee_id) AS manpower'),
            ])
            ->where('assignment.client_id', $clientId)
            ->groupBy('assignment.client_id', 'assignment.work_realization_id');

        $query = DB::table('work_realizations as realization')
            ->leftJoin('shifts', function ($join): void {
                $join->on('shifts.id', '=', 'realization.shift_id')
                    ->on('shifts.client_id', '=', 'realization.client_id');
            })
            ->join('products', function ($join): void {
                $join->on('products.id', '=', 'realization.product_id')
                    ->on('products.client_id', '=', 'realization.client_id');
            })
            ->leftJoin('cost_centers', function ($join): void {
                $join->on('cost_centers.id', '=', 'products.cost_center_id')
                    ->on('cost_centers.client_id', '=', 'realization.client_id');
            })
            ->leftJoin('batches', function ($join): void {
                $join->on('batches.id', '=', 'realization.batch_id')
                    ->on('batches.client_id', '=', 'realization.client_id');
            })
            ->leftJoinSub($assignmentCounts, 'assignment_counts', function ($join): void {
                $join->on('assignment_counts.work_realization_id', '=', 'realization.id')
                    ->on('assignment_counts.client_id', '=', 'realization.client_id');
            })
            ->where('realization.client_id', $clientId)
            ->select([
                DB::raw("'TBC' AS invoice"),
                DB::raw("DATE_FORMAT(realization.work_date, '%Y-%m') AS period"),
                'shifts.name AS shift',
                'realization.sku_snapshot AS sku',
                'cost_centers.name AS cost_center',
                'batches.batch_no AS batch',
                'realization.unit_name_snapshot AS unit',
                DB::raw('SUM(COALESCE(realization.total_output, 0)) AS qty'),
                DB::raw('SUM(COALESCE(assignment_counts.manpower, 0)) AS manpower'),
                'products.po_price',
                DB::raw('SUM(COALESCE(realization.total_output, 0) * COALESCE(products.po_price, 0)) AS amount_po'),
            ])
            ->groupBy([
                DB::raw("DATE_FORMAT(realization.work_date, '%Y-%m')"),
                'shifts.name',
                'realization.sku_snapshot',
                'cost_centers.name',
                'batches.batch_no',
                'realization.unit_name_snapshot',
                'products.po_price',
            ])
            ->orderByDesc('period')
            ->orderBy('shift')
            ->orderBy('sku');

        if ($period !== null) {
            $month = Carbon::createFromFormat('Y-m', $period);
            $query->whereBetween('realization.work_date', [
                $month->copy()->startOfMonth()->toDateString(),
                $month->copy()->endOfMonth()->toDateString(),
            ]);
        }

        if ($shiftId !== null) {
            $query->where('realization.shift_id', $shiftId);
        }

        if ($costCenterId !== null) {
            $query->where('products.cost_center_id', $costCenterId);
        }

        if ($sku !== null && $sku !== '') {
            $query->where('realization.sku_snapshot', 'like', "%{$sku}%");
        }

        if ($batchNo !== null && $batchNo !== '') {
            $query->where('batches.batch_no', 'like', "%{$batchNo}%");
        }

        return $query;
    }
}
