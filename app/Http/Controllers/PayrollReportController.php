<?php

namespace App\Http\Controllers;

use App\Exports\PayrollReportExport;
use App\Services\CurrentClientService;
use App\Services\PayrollReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;

class PayrollReportController extends Controller
{
    public function __invoke(Request $request, CurrentClientService $client, PayrollReportBuilder $builder): View|JsonResponse
    {
        $ownPayroll = $this->isOwnPayroll($request, $client);
        abort_unless($request->user()->canAccessMenu($ownPayroll ? 'my-payroll' : 'reports', $client->get()), 403);
        [$dateFrom, $dateTo, $period] = $this->resolveRange($request);
        $query = $builder->forClient($client->id(), $dateFrom, $dateTo);
        $this->scopeToUser($query, $request, $ownPayroll);

        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::eloquent($query)
                ->filterColumn('attendance_days', function (Builder $query, string $keyword): void {
                    $query->whereRaw('COALESCE(attendance.attendance_days, 0) LIKE ?', ["%{$keyword}%"]);
                })
                ->filterColumn('gross_salary', function (Builder $query, string $keyword): void {
                    $query->whereRaw('COALESCE(attendance.gross_salary, 0) LIKE ?', ["%{$keyword}%"]);
                })
                ->filterColumn('uniform_amount', function (Builder $query, string $keyword): void {
                    $query->whereRaw('COALESCE(deductions.uniform_amount, 0) LIKE ?', ["%{$keyword}%"]);
                })
                ->filterColumn('equipment_amount', function (Builder $query, string $keyword): void {
                    $query->whereRaw('COALESCE(deductions.equipment_amount, 0) LIKE ?', ["%{$keyword}%"]);
                })
                ->filterColumn('meal_amount', function (Builder $query, string $keyword): void {
                    $query->whereRaw('COALESCE(deductions.meal_amount, 0) LIKE ?', ["%{$keyword}%"]);
                })
                ->filterColumn('salary_advance_value', function (Builder $query, string $keyword): void {
                    $query->whereRaw('COALESCE(deductions.salary_advance_value, 0) LIKE ?', ["%{$keyword}%"]);
                })
                ->filterColumn('correction_minus', function (Builder $query, string $keyword): void {
                    $query->whereRaw('COALESCE(deductions.correction_minus, 0) LIKE ?', ["%{$keyword}%"]);
                })
                ->filterColumn('correction_plus', function (Builder $query, string $keyword): void {
                    $query->whereRaw('COALESCE(deductions.correction_plus, 0) LIKE ?', ["%{$keyword}%"]);
                })
                ->orderColumn('attendance_days', 'COALESCE(attendance.attendance_days, 0) $1')
                ->orderColumn('gross_salary', 'COALESCE(attendance.gross_salary, 0) $1')
                ->orderColumn('uniform_amount', 'COALESCE(deductions.uniform_amount, 0) $1')
                ->orderColumn('equipment_amount', 'COALESCE(deductions.equipment_amount, 0) $1')
                ->orderColumn('meal_amount', 'COALESCE(deductions.meal_amount, 0) $1')
                ->orderColumn('salary_advance_value', 'COALESCE(deductions.salary_advance_value, 0) $1')
                ->orderColumn('correction_minus', 'COALESCE(deductions.correction_minus, 0) $1')
                ->orderColumn('correction_plus', 'COALESCE(deductions.correction_plus, 0) $1')
                ->editColumn('gender', fn (object $row): string => $row->gender === 'male' ? 'Laki-laki' : 'Perempuan')
                ->addColumn('bpjs_health', fn (object $row): int => PayrollReportBuilder::bpjsHealth($row))
                ->addColumn('bpjs_employment', fn (object $row): int => PayrollReportBuilder::bpjsEmployment($row))
                ->addColumn('net_salary', fn (object $row): int => PayrollReportBuilder::netSalary($row))
                ->toJson();
        }

        return view('reports.payroll', ['currentClient' => $client->get(), 'user' => $request->user(), 'period' => $period, 'isOwnPayroll' => $ownPayroll]);
    }

    public function exportExcel(Request $request, CurrentClientService $client): BinaryFileResponse
    {
        $ownPayroll = $this->isOwnPayroll($request, $client);
        abort_unless($request->user()->canAccessMenu($ownPayroll ? 'my-payroll' : 'reports', $client->get()), 403);
        [$dateFrom, $dateTo] = $this->resolveRange($request);

        return Excel::download(
            new PayrollReportExport($client->id(), $dateFrom, $dateTo, $ownPayroll ? $request->user()->id : null),
            "laporan-payroll-{$this->periodFileLabel($request, $dateFrom, $dateTo)}.xlsx",
        );
    }

    public function exportPdf(Request $request, CurrentClientService $client, PayrollReportBuilder $builder): Response
    {
        $ownPayroll = $this->isOwnPayroll($request, $client);
        abort_unless($request->user()->canAccessMenu($ownPayroll ? 'my-payroll' : 'reports', $client->get()), 403);
        [$dateFrom, $dateTo] = $this->resolveRange($request);
        $query = $builder->forClient($client->id(), $dateFrom, $dateTo);
        $this->scopeToUser($query, $request, $ownPayroll);
        $rows = $query->lazy(500);
        $pdf = Pdf::loadView('reports.payroll-pdf', [
            'client' => $client->get(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        return $pdf->download("laporan-payroll-{$this->periodFileLabel($request, $dateFrom, $dateTo)}.pdf");
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function resolveRange(Request $request): array
    {
        $validated = $request->validate([
            'period' => ['nullable', 'date_format:Y-m'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        if ($validated['period'] ?? null) {
            $month = Carbon::createFromFormat('!Y-m', $validated['period']);

            return [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString(), $validated['period']];
        }

        $dateFrom = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $validated['date_to'] ?? now()->endOfMonth()->toDateString();

        return [
            $dateFrom,
            $dateTo,
            substr($dateFrom, 0, 7),
        ];
    }

    private function periodFileLabel(Request $request, string $dateFrom, string $dateTo): string
    {
        return $request->filled('period') ? $request->string('period')->toString() : "{$dateFrom}-{$dateTo}";
    }

    private function isOwnPayroll(Request $request, CurrentClientService $client): bool
    {
        return in_array($request->user()->roleCodeFor($client->get()), ['leader', 'employee'], true);
    }

    private function scopeToUser(Builder $query, Request $request, bool $ownPayroll): void
    {
        if ($ownPayroll) {
            $query->where('employees.user_id', $request->user()->id);
        }
    }
}
