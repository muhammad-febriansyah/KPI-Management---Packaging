<?php

namespace App\Http\Controllers;

use App\Exports\InvoiceReportExport;
use App\Services\CurrentClientService;
use App\Services\InvoiceReportQuery;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class InvoiceReportController extends Controller
{
    public function index(Request $request, CurrentClientService $client, InvoiceReportQuery $report): View|JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('invoice-report', $client->get()), 403);
        [$periodFrom, $periodTo] = $this->filters($request);

        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::query($report->build($client->id(), $periodFrom, $periodTo, ordered: false))
                ->filterColumn('period', fn (Builder $query, string $keyword): Builder => $query->whereRaw("DATE_FORMAT(realization.work_date, '%m-%Y') LIKE ?", ["%{$keyword}%"]))
                ->filterColumn('sku', fn (Builder $query, string $keyword): Builder => $query->where('realization.sku_snapshot', 'like', "%{$keyword}%"))
                ->filterColumn('cost_center', fn (Builder $query, string $keyword): Builder => $query->where('cost_centers.name', 'like', "%{$keyword}%"))
                ->filterColumn('unit', fn (Builder $query, string $keyword): Builder => $query->where('realization.unit_name_snapshot', 'like', "%{$keyword}%"))
                ->orderColumn('period', 'period $1, sku asc, cost_center asc')
                ->orderColumn('sku', 'realization.sku_snapshot $1')
                ->orderColumn('cost_center', 'cost_centers.name $1')
                ->orderColumn('qty', 'qty $1')
                ->orderColumn('unit', 'unit $1')
                ->orderColumn('po_price', 'po_price $1')
                ->orderColumn('total_po', 'total_po $1')
                ->editColumn('period', fn (object $row): string => date('m-Y', strtotime($row->period.'-01')))
                ->editColumn('cost_center', fn (object $row): string => $row->cost_center ?: '—')
                ->editColumn('qty', fn (object $row): string => $this->formatQuantity($row->qty))
                ->editColumn('unit', fn (object $row): string => $row->unit ?: '—')
                ->editColumn('po_price', fn (object $row): string => $this->formatCurrency($row->po_price))
                ->editColumn('total_po', fn (object $row): string => $this->formatCurrency($row->total_po))
                ->with('summary', ['total_po' => $this->formatCurrency($report->totalPo($client->id(), $periodFrom, $periodTo))])
                ->toJson();
        }

        return view('reports.invoice', [
            'currentClient' => $client->get(),
            'user' => $request->user(),
            'periodFrom' => $periodFrom,
            'periodTo' => $periodTo,
        ]);
    }

    public function exportExcel(Request $request, CurrentClientService $client): BinaryFileResponse
    {
        abort_unless($request->user()->canAccessMenu('invoice-report', $client->get()), 403);
        [$periodFrom, $periodTo] = $this->filters($request);
        $label = ($periodFrom ?? 'awal').'-sd-'.($periodTo ?? 'sekarang');

        return Excel::download(new InvoiceReportExport($client->id(), $periodFrom, $periodTo), "report-invoice-{$label}.xlsx");
    }

    /** @return array{0: ?string, 1: ?string} */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'period_from' => ['nullable', 'date_format:Y-m'],
            'period_to' => ['nullable', 'date_format:Y-m', 'after_or_equal:period_from'],
        ]);

        return [$validated['period_from'] ?? null, $validated['period_to'] ?? null];
    }

    private function formatQuantity(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 3, ',', '.'), '0'), ',');
    }

    private function formatCurrency(mixed $value): string
    {
        return 'Rp '.number_format((int) round((float) ($value ?? 0)), 0, ',', '.');
    }
}
