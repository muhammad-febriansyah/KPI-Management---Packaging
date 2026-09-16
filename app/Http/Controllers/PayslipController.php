<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\CurrentClientService;
use App\Services\PayrollReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PayslipController extends Controller
{
    public function show(Request $request, Employee $employee, CurrentClientService $client, PayrollReportBuilder $builder): Response
    {
        abort_unless($request->user()->canAccessMenu('reports', $client->get()), 403);
        [$dateFrom, $dateTo] = $this->resolveRange($request);
        $row = $builder->forClient($client->id(), $dateFrom, $dateTo)->where('employees.id', $employee->id)->first();
        abort_unless($row, 404);

        $pdf = Pdf::loadView('reports.payslip', ['rows' => [$row], 'client' => $client->get(), 'dateFrom' => $dateFrom, 'dateTo' => $dateTo]);

        return $pdf->download("slip-gaji-{$row->employee_no}.pdf");
    }

    public function bulk(Request $request, CurrentClientService $client, PayrollReportBuilder $builder): Response
    {
        abort_unless($request->user()->canAccessMenu('reports', $client->get()), 403);
        [$dateFrom, $dateTo] = $this->resolveRange($request);
        $rows = $builder->forClient($client->id(), $dateFrom, $dateTo)->lazy(500);

        $pdf = Pdf::loadView('reports.payslip', ['rows' => $rows, 'client' => $client->get(), 'dateFrom' => $dateFrom, 'dateTo' => $dateTo]);

        return $pdf->download('slip-gaji-'.now()->format('Ymd-His').'.pdf');
    }

    /** @return array{0: string, 1: string} */
    private function resolveRange(Request $request): array
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);
        $dateFrom = $validated['date_from'] ?? now()->startOfMonth()->toDateString();
        $dateTo = $validated['date_to'] ?? now()->endOfMonth()->toDateString();

        return [$dateFrom, $dateTo];
    }
}
