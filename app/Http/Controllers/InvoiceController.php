<?php

namespace App\Http\Controllers;

use App\Exports\InvoiceExport;
use App\Models\CostCenter;
use App\Models\Shift;
use App\Services\CurrentClientService;
use App\Services\InvoiceBoronganQuery;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class InvoiceController extends Controller
{
    public function index(Request $request, CurrentClientService $client, InvoiceBoronganQuery $invoiceQuery): View|JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('invoices', $client->get()), 403);
        $filters = $this->filters($request);
        $query = $invoiceQuery->build($client->id(), ...$filters);

        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::query($query)
                ->filterColumn('invoice', fn (Builder $query, string $keyword): Builder => $query->whereRaw("'TBC' LIKE ?", ["%{$keyword}%"]))
                ->filterColumn('period', fn (Builder $query, string $keyword): Builder => $query->whereRaw("DATE_FORMAT(realization.work_date, '%Y-%m') LIKE ?", ["%{$keyword}%"]))
                ->filterColumn('shift', fn (Builder $query, string $keyword): Builder => $query->where('shifts.name', 'like', "%{$keyword}%"))
                ->filterColumn('sku', fn (Builder $query, string $keyword): Builder => $query->where('realization.sku_snapshot', 'like', "%{$keyword}%"))
                ->filterColumn('cost_center', fn (Builder $query, string $keyword): Builder => $query->where('cost_centers.name', 'like', "%{$keyword}%"))
                ->filterColumn('batch', fn (Builder $query, string $keyword): Builder => $query->where('batches.batch_no', 'like', "%{$keyword}%"))
                ->filterColumn('unit', fn (Builder $query, string $keyword): Builder => $query->where('realization.unit_name_snapshot', 'like', "%{$keyword}%"))
                ->filterColumn('qty', fn (Builder $query, string $keyword): Builder => $query->havingRaw('SUM(COALESCE(realization.total_output, 0)) LIKE ?', ["%{$keyword}%"]))
                ->filterColumn('manpower', fn (Builder $query, string $keyword): Builder => $query->havingRaw('SUM(COALESCE(assignment_counts.manpower, 0)) LIKE ?', ["%{$keyword}%"]))
                ->filterColumn('po_price', fn (Builder $query, string $keyword): Builder => $query->where('products.po_price', 'like', "%{$keyword}%"))
                ->filterColumn('amount_po', fn (Builder $query, string $keyword): Builder => $query->havingRaw('SUM(COALESCE(realization.total_output, 0) * COALESCE(products.po_price, 0)) LIKE ?', ["%{$keyword}%"]))
                ->orderColumn('period', 'period $1')
                ->orderColumn('shift', 'shifts.name $1')
                ->orderColumn('sku', 'realization.sku_snapshot $1')
                ->orderColumn('cost_center', 'cost_centers.name $1')
                ->orderColumn('batch', 'batches.batch_no $1')
                ->orderColumn('qty', 'SUM(COALESCE(realization.total_output, 0)) $1')
                ->orderColumn('manpower', 'SUM(COALESCE(assignment_counts.manpower, 0)) $1')
                ->orderColumn('po_price', 'products.po_price $1')
                ->orderColumn('amount_po', 'SUM(COALESCE(realization.total_output, 0) * COALESCE(products.po_price, 0)) $1')
                ->editColumn('period', fn (object $row): string => $row->period ? date('m/Y', strtotime($row->period.'-01')) : '—')
                ->editColumn('shift', fn (object $row): string => $row->shift ?: '—')
                ->editColumn('cost_center', fn (object $row): string => $row->cost_center ?: '—')
                ->editColumn('batch', fn (object $row): string => $row->batch ?: '—')
                ->editColumn('qty', fn (object $row): string => $this->formatQuantity($row->qty))
                ->editColumn('manpower', fn (object $row): int => (int) $row->manpower)
                ->editColumn('po_price', fn (object $row): string => $this->formatCurrency($row->po_price))
                ->editColumn('amount_po', fn (object $row): string => $this->formatCurrency($row->amount_po))
                ->toJson();
        }

        return view('invoices.index', [
            'currentClient' => $client->get(),
            'user' => $request->user(),
            'period' => $filters[0] ?? now()->format('Y-m'),
            'shifts' => Shift::query()->where('client_id', $client->id())->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'costCenters' => CostCenter::query()->where('client_id', $client->id())->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function exportExcel(Request $request, CurrentClientService $client): BinaryFileResponse
    {
        abort_unless($request->user()->canAccessMenu('invoices', $client->get()), 403);
        [$period, $shiftId, $costCenterId, $sku, $batchNo] = $this->filters($request);
        $periodLabel = $period ?? 'semua-periode';

        return Excel::download(
            new InvoiceExport($client->id(), $period, $shiftId, $costCenterId, $sku, $batchNo),
            "invoice-borongan-{$periodLabel}.xlsx",
        );
    }

    /** @return array{0: ?string, 1: ?int, 2: ?int, 3: ?string, 4: ?string} */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'period' => ['nullable', 'date_format:Y-m'],
            'shift_id' => ['nullable', 'integer'],
            'cost_center_id' => ['nullable', 'integer'],
            'sku' => ['nullable', 'string', 'max:100'],
            'batch_no' => ['nullable', 'string', 'max:100'],
        ]);

        return [
            $validated['period'] ?? null,
            isset($validated['shift_id']) && $validated['shift_id'] !== '' ? (int) $validated['shift_id'] : null,
            isset($validated['cost_center_id']) && $validated['cost_center_id'] !== '' ? (int) $validated['cost_center_id'] : null,
            $validated['sku'] ?? null,
            $validated['batch_no'] ?? null,
        ];
    }

    private function formatQuantity(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
    }

    private function formatCurrency(mixed $value): string
    {
        return 'Rp '.number_format((int) round((float) ($value ?? 0)), 0, ',', '.');
    }
}
