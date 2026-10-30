<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class InvoiceReportQuery
{
    /**
     * Realizations of one client grouped by month, SKU, and cost center, priced at the
     * product's master PO price.
     */
    public function build(int $clientId, ?string $periodFrom = null, ?string $periodTo = null, bool $ordered = true): Builder
    {
        $query = $this->filtered($clientId, $periodFrom, $periodTo)
            ->select([
                DB::raw("DATE_FORMAT(realization.work_date, '%Y-%m') AS period"),
                'realization.sku_snapshot AS sku',
                'cost_centers.name AS cost_center',
                DB::raw('MAX(realization.unit_name_snapshot) AS unit'),
                DB::raw('SUM(COALESCE(realization.total_output, 0)) AS qty'),
                DB::raw('MAX(COALESCE(products.po_price, 0)) AS po_price'),
                DB::raw('SUM(COALESCE(realization.total_output, 0) * COALESCE(products.po_price, 0)) AS total_po'),
            ])
            ->groupBy([
                DB::raw("DATE_FORMAT(realization.work_date, '%Y-%m')"),
                'realization.sku_snapshot',
                'cost_centers.name',
            ]);

        // A DataTable adds the column the user clicked, so it asks for the unordered query.
        return $ordered
            ? $query->orderByDesc('period')->orderBy('sku')->orderBy('cost_center')
            : $query;
    }

    public function totalPo(int $clientId, ?string $periodFrom = null, ?string $periodTo = null): int
    {
        return (int) round((float) $this->filtered($clientId, $periodFrom, $periodTo)
            ->sum(DB::raw('COALESCE(realization.total_output, 0) * COALESCE(products.po_price, 0)')));
    }

    private function filtered(int $clientId, ?string $periodFrom, ?string $periodTo): Builder
    {
        $query = DB::table('work_realizations as realization')
            ->join('products', function ($join): void {
                $join->on('products.id', '=', 'realization.product_id')
                    ->on('products.client_id', '=', 'realization.client_id');
            })
            ->leftJoin('cost_centers', function ($join): void {
                $join->on('cost_centers.id', '=', 'products.cost_center_id')
                    ->on('cost_centers.client_id', '=', 'realization.client_id');
            })
            ->where('realization.client_id', $clientId);

        if ($periodFrom !== null) {
            $query->where('realization.work_date', '>=', Carbon::createFromFormat('Y-m', $periodFrom)->startOfMonth()->toDateString());
        }

        if ($periodTo !== null) {
            $query->where('realization.work_date', '<=', Carbon::createFromFormat('Y-m', $periodTo)->endOfMonth()->toDateString());
        }

        return $query;
    }
}
