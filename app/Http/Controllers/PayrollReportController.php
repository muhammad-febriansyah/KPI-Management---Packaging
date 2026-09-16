<?php

namespace App\Http\Controllers;

use App\Exports\PayrollReportExport;
use App\Services\CurrentClientService;
use App\Services\PayrollReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
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
        abort_unless($request->user()->canAccessMenu('reports', $client->get()), 403);
        [$dateFrom, $dateTo] = $this->resolveRange($request);
        $query = $builder->forClient($client->id(), $dateFrom, $dateTo);

        if ($request->has('draw') || $request->expectsJson()) {
            return DataTables::eloquent($query)
                ->editColumn('gender', fn (object $row): string => $row->gender === 'male' ? 'Laki-laki' : 'Perempuan')
                ->addColumn('bpjs_employment', fn (object $row): int => PayrollReportBuilder::bpjsEmployment($row))
                ->addColumn('net_salary', fn (object $row): int => PayrollReportBuilder::netSalary($row))
                ->toJson();
        }

        return view('reports.payroll', ['currentClient' => $client->get(), 'user' => $request->user(), 'dateFrom' => $dateFrom, 'dateTo' => $dateTo]);
    }

    public function exportExcel(Request $request, CurrentClientService $client): BinaryFileResponse
    {
        abort_unless($request->user()->canAccessMenu('reports', $client->get()), 403);
        [$dateFrom, $dateTo] = $this->resolveRange($request);

        return Excel::download(
            new PayrollReportExport($client->id(), $dateFrom, $dateTo),
            "laporan-payroll-{$dateFrom}-{$dateTo}.xlsx",
        );
    }

    public function exportPdf(Request $request, CurrentClientService $client, PayrollReportBuilder $builder): Response
    {
        abort_unless($request->user()->canAccessMenu('reports', $client->get()), 403);
        [$dateFrom, $dateTo] = $this->resolveRange($request);
        $rows = $builder->forClient($client->id(), $dateFrom, $dateTo)->lazy(500);
        $pdf = Pdf::loadView('reports.payroll-pdf', [
            'client' => $client->get(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'rows' => $rows,
        ])->setPaper('a4', 'landscape');

        return $pdf->download("laporan-payroll-{$dateFrom}-{$dateTo}.pdf");
    }

    /** @return array{0: string, 1: string} */
    private function resolveRange(Request $request): array
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        return [
            $validated['date_from'] ?? now()->startOfMonth()->toDateString(),
            $validated['date_to'] ?? now()->endOfMonth()->toDateString(),
        ];
    }
}
