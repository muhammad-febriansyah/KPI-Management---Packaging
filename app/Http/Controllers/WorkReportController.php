<?php

namespace App\Http\Controllers;

use App\Exports\WorkReportExport;
use App\Services\CurrentClientService;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class WorkReportController extends Controller
{
    public function __invoke(Request $request, CurrentClientService $client): View|JsonResponse
    {
        abort_unless($request->user()->canAccessMenu('work-reports', $client->get()), 403);
        [$dateFrom, $dateTo] = $this->dateFilters($request);

        $query = $this->realizationQuery($request, $client);

        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::query($query)
                ->filterColumn('sim_id', function (Builder $query, string $keyword): void {
                    $query->where(function (Builder $query) use ($keyword): void {
                        $query->where('employees.sim_id', 'like', "%{$keyword}%")
                            ->orWhere('employees.employee_no', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('employee_no', fn (Builder $query, string $keyword): Builder => $query->where('employees.employee_no', 'like', "%{$keyword}%"))
                ->filterColumn('full_name', function (Builder $query, string $keyword): void {
                    $query->where('employees.full_name', 'like', "%{$keyword}%");
                })
                ->filterColumn('shift_name', function (Builder $query, string $keyword): void {
                    $query->where('shifts.name', 'like', "%{$keyword}%");
                })
                ->filterColumn('product_name', function (Builder $query, string $keyword): void {
                    $query->where('realization.product_name_snapshot', 'like', "%{$keyword}%");
                })
                ->filterColumn('actual', function (Builder $query, string $keyword): void {
                    $query->where('realization.total_output', 'like', "%{$keyword}%");
                })
                ->filterColumn('description', function (Builder $query, string $keyword): void {
                    $query->where('realization.report', 'like', "%{$keyword}%");
                })
                ->orderColumn('sim_id', 'COALESCE(employees.sim_id, employees.employee_no) $1')
                ->orderColumn('employee_no', 'employees.employee_no $1')
                ->orderColumn('full_name', 'employees.full_name $1')
                ->orderColumn('shift_name', 'shifts.name $1')
                ->orderColumn('product_name', 'realization.product_name_snapshot $1')
                ->orderColumn('actual', 'realization.total_output $1')
                ->orderColumn('description', 'realization.report $1')
                ->editColumn('work_date', fn (object $row): string => $row->work_date ? date('d/m/Y', strtotime($row->work_date)) : '—')
                ->addColumn('target', fn (object $row): string => $this->formatQuantity($this->targetOutput($row)))
                ->addColumn('target_price', fn (object $row): string => $this->formatCurrency($this->targetOutput($row), $row->po_price))
                ->editColumn('actual', fn (object $row): string => $this->formatQuantity($row->actual))
                ->addColumn('actual_price', fn (object $row): string => $this->formatCurrency($row->actual, $row->po_price))
                ->toJson();
        }

        return view('reports.work', ['currentClient' => $client->get(), 'user' => $request->user()]);
    }

    public function exportExcel(Request $request, CurrentClientService $client): BinaryFileResponse
    {
        abort_unless($request->user()->canAccessMenu('work-reports', $client->get()), 403);
        [$dateFrom, $dateTo] = $this->dateFilters($request);
        $employeeUserId = ! $request->user()->is_super_admin && $request->user()->roleCodeFor($client->get()) === 'employee'
            ? $request->user()->id
            : null;
        $period = $dateFrom === null && $dateTo === null
            ? 'semua-periode'
            : ($dateFrom ?? 'awal').'-'.($dateTo ?? 'akhir');

        return Excel::download(
            new WorkReportExport($client->id(), $dateFrom, $dateTo, $employeeUserId),
            "hasil-pekerjaan-{$period}.xlsx",
        );
    }

    private function realizationQuery(Request $request, CurrentClientService $client): Builder
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
            ->where('realization.client_id', $client->id())
            ->select([
                'assignment.id as assignment_id',
                'realization.id as realization_id',
                'realization.work_date',
                'employees.employee_no',
                'shifts.name as shift_name',
                'realization.sku_snapshot',
                DB::raw('COALESCE(employees.sim_id, employees.employee_no) AS sim_id'),
                'employees.full_name',
                'realization.product_name_snapshot as product_name',
                'realization.total_output as actual',
                'realization.start_time',
                'realization.end_time',
                'products.estimated_output_per_hour',
                'products.po_price',
                DB::raw("COALESCE(realization.report, '') AS description"),
            ])
            ->join('shifts', function ($join): void {
                $join->on('shifts.id', '=', 'realization.shift_id')
                    ->on('shifts.client_id', '=', 'realization.client_id');
            })
            ->join('products', function ($join): void {
                $join->on('products.id', '=', 'realization.product_id')
                    ->on('products.client_id', '=', 'realization.client_id');
            })
            ->orderByDesc('realization.work_date')
            ->orderByDesc('realization.id')
            ->orderBy('assignment.id');

        if ($request->filled('date_from')) {
            $query->where('realization.work_date', '>=', $request->string('date_from')->toString());
        }

        if ($request->filled('date_to')) {
            $query->where('realization.work_date', '<=', $request->string('date_to')->toString());
        }

        if (! $request->user()->is_super_admin && $request->user()->roleCodeFor($client->get()) === 'employee') {
            $query->where('employees.user_id', $request->user()->id);
        }

        return $query;
    }

    /** @return array{0: ?string, 1: ?string} */
    private function dateFilters(Request $request): array
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        return [$validated['date_from'] ?? null, $validated['date_to'] ?? null];
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

    private function formatQuantity(mixed $quantity): string
    {
        if ($quantity === null || $quantity === '') {
            return '—';
        }

        $formatted = number_format((float) $quantity, 3, ',', '.');

        return rtrim(rtrim($formatted, '0'), ',');
    }

    private function formatCurrency(?float $quantity, mixed $unitPrice): string
    {
        if ($quantity === null || $quantity === '' || $unitPrice === null || $unitPrice === '') {
            return '—';
        }

        return 'Rp '.number_format((int) round((float) $quantity * (float) $unitPrice), 0, ',', '.');
    }
}
