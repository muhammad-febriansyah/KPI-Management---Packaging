<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Employee;
use App\Models\Product;
use App\Models\RealizationEmployee;
use App\Models\User;
use App\Models\WorkRealization;
use App\Services\CurrentClientService;
use App\Services\PayrollReportBuilder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, CurrentClientService $currentClients, PayrollReportBuilder $payrollBuilder): View
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $availableClients = $currentClients->availableFor($user);
        $currentClientId = $request->session()->get('current_client_id');
        $currentClient = is_int($currentClientId)
            ? $availableClients->firstWhere('id', $currentClientId)
            : null;

        $currentClient ??= $availableClients->first();

        if ($currentClient !== null) {
            $request->session()->put('current_client_id', $currentClient->getKey());
            $currentClients->set($currentClient);
        } else {
            $request->session()->forget('current_client_id');
        }

        $roleCode = $currentClient !== null ? $user->roleCodeFor($currentClient) : null;

        if ($currentClient !== null && $roleCode === 'leader') {
            return $this->leaderDashboard($user, $currentClient, $availableClients, $payrollBuilder);
        }

        if ($currentClient !== null && $roleCode === 'employee') {
            return $this->employeeDashboard($user, $currentClient, $availableClients, $payrollBuilder);
        }

        return view('dashboard', [
            'availableClients' => $availableClients,
            'currentClient' => $currentClient,
            'user' => $user,
            'metrics' => [
                'employees' => $currentClient ? Employee::where('client_id', $currentClient->id)->where('status', 'active')->count() : 0,
                'products' => $currentClient ? Product::where('client_id', $currentClient->id)->where('status', 'active')->count() : 0,
                'realizations' => $currentClient ? WorkRealization::where('client_id', $currentClient->id)->where('work_date', today()->toDateString())->count() : 0,
                'output' => $currentClient ? WorkRealization::where('client_id', $currentClient->id)->where('work_date', today()->toDateString())->sum('total_output') : 0,
                'complaints' => $currentClient ? WorkRealization::where('client_id', $currentClient->id)->where('is_complaint', true)->count() : 0,
            ],
            'outputTrend' => $currentClient ? $this->monthlyOutputTrend($currentClient->id) : [],
            'shiftTrend' => $currentClient ? $this->monthlyShiftOutput($currentClient->id) : [],
        ]);
    }

    private function leaderDashboard(User $user, mixed $currentClient, mixed $availableClients, PayrollReportBuilder $payrollBuilder): View
    {
        $own = $this->ownedRealizations($user, $currentClient->id);
        $today = today()->toDateString();
        $todayRealizationIds = (clone $own)->whereDate('work_date', $today)->select('id');
        $payroll = $this->payrollForUser($user, $currentClient->id, $payrollBuilder);
        $payrollSummary = $this->payrollSummary($payroll);

        return view('dashboard-leader', [
            'availableClients' => $availableClients,
            'currentClient' => $currentClient,
            'user' => $user,
            'metrics' => [
                'realizationsToday' => (clone $own)->whereDate('work_date', $today)->count(),
                'outputToday' => (clone $own)->whereDate('work_date', $today)->sum('total_output'),
                'activeBatches' => Batch::query()->where('client_id', $currentClient->id)->where('status', 'active')->count(),
                'unassignedToday' => (clone $own)->whereDate('work_date', $today)->whereDoesntHave('employeeAssignments')->count(),
                'assignedEmployeesToday' => RealizationEmployee::query()
                    ->where('client_id', $currentClient->id)
                    ->whereIn('work_realization_id', $todayRealizationIds)
                    ->distinct('employee_id')
                    ->count('employee_id'),
                'complaintsToday' => (clone $own)->whereDate('work_date', $today)->where('is_complaint', true)->count(),
            ],
            'recentRealizations' => (clone $own)
                ->with(['shift:id,client_id,name', 'batch:id,client_id,batch_no', 'product:id,client_id,sku,name'])
                ->withCount('employeeAssignments')
                ->latest('work_date')
                ->latest('id')
                ->limit(8)
                ->get(),
            'shiftSummaries' => (clone $own)
                ->select('shift_id')
                ->selectRaw('COUNT(*) AS realizations_count')
                ->selectRaw('COALESCE(SUM(total_output), 0) AS total_output')
                ->with('shift:id,client_id,name')
                ->groupBy('shift_id')
                ->orderByDesc('total_output')
                ->get(),
            'payroll' => $payroll,
            'payrollNet' => $payrollSummary['net'],
            'payrollSummary' => $payrollSummary,
            'outputTrend' => $this->outputTrend($own),
        ]);
    }

    private function employeeDashboard(User $user, mixed $currentClient, mixed $availableClients, PayrollReportBuilder $payrollBuilder): View
    {
        $own = $this->ownedRealizations($user, $currentClient->id);
        $payroll = $this->payrollForUser($user, $currentClient->id, $payrollBuilder);
        $payrollSummary = $this->payrollSummary($payroll);
        $employee = Employee::query()
            ->with('group:id,client_id,name')
            ->where('client_id', $currentClient->id)
            ->where('user_id', $user->id)
            ->first();

        return view('dashboard-employee', [
            'availableClients' => $availableClients,
            'currentClient' => $currentClient,
            'user' => $user,
            'employee' => $employee,
            'payroll' => $payroll,
            'payrollNet' => $payrollSummary['net'],
            'payrollSummary' => $payrollSummary,
            'outputTrend' => $this->outputTrend($own),
            'metrics' => [
                'realizationsToday' => (clone $own)->whereDate('work_date', today()->toDateString())->count(),
                'outputToday' => (clone $own)->whereDate('work_date', today()->toDateString())->sum('total_output'),
                'outputThisMonth' => (clone $own)->whereBetween('work_date', [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()])->sum('total_output'),
                'realizationsThisMonth' => (clone $own)->whereBetween('work_date', [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()])->count(),
                'realizationsTotal' => (clone $own)->count(),
            ],
            'recentRealizations' => (clone $own)->with(['shift', 'product'])->latest('work_date')->latest('id')->limit(8)->get(),
        ]);
    }

    private function ownedRealizations(User $user, int $clientId): Builder
    {
        return WorkRealization::query()
            ->where('client_id', $clientId)
            ->where(function ($query) use ($user): void {
                $query->where('created_by', $user->id)
                    ->orWhereHas('employeeAssignments.employee', fn ($employeeQuery) => $employeeQuery->where('user_id', $user->id));
            });
    }

    private function payrollForUser(User $user, int $clientId, PayrollReportBuilder $payrollBuilder): ?Employee
    {
        return $payrollBuilder
            ->forClient($clientId, today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString())
            ->where('employees.user_id', $user->id)
            ->first();
    }

    /** @return array<int, array{label: string, value: float}> */
    private function monthlyOutputTrend(int $clientId): array
    {
        $start = today()->startOfMonth()->subMonths(11);
        $end = today()->endOfMonth();
        $totals = WorkRealization::query()
            ->where('client_id', $clientId)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get(['work_date', 'total_output'])
            ->groupBy(fn (WorkRealization $realization): string => Carbon::parse($realization->work_date)->format('Y-m'))
            ->map(fn ($items): float => (float) $items->sum('total_output'));

        return collect(range(11, 0))->map(function (int $monthsAgo) use ($start, $totals): array {
            $month = $start->copy()->addMonths($monthsAgo);

            return ['label' => $month->translatedFormat('M y'), 'value' => $totals[$month->format('Y-m')] ?? 0.0];
        })->all();
    }

    /** @return array<int, array{label: string, value: float}> */
    private function monthlyShiftOutput(int $clientId): array
    {
        return WorkRealization::query()
            ->where('client_id', $clientId)
            ->whereBetween('work_date', [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()])
            ->select('shift_id')
            ->selectRaw('COALESCE(SUM(total_output), 0) AS total_output')
            ->with('shift:id,name')
            ->groupBy('shift_id')
            ->orderByDesc('total_output')
            ->get()
            ->map(fn (WorkRealization $realization): array => [
                'label' => $realization->shift?->name ?? 'Tanpa shift',
                'value' => (float) $realization->total_output,
            ])->values()->all();
    }

    /**
     * Return payroll values used by dashboard summaries without changing the
     * report builder's calculation contract.
     *
     * @return array{gross: int, deductions: int, correctionPlus: int, net: int, bpjsHealth: int, bpjsEmployment: int, advance: int}
     */
    private function payrollSummary(?object $payroll): array
    {
        if ($payroll === null) {
            return [
                'gross' => 0,
                'deductions' => 0,
                'correctionPlus' => 0,
                'net' => 0,
                'bpjsHealth' => 0,
                'bpjsEmployment' => 0,
                'advance' => 0,
            ];
        }

        $bpjsHealth = PayrollReportBuilder::bpjsHealth($payroll);
        $bpjsEmployment = PayrollReportBuilder::bpjsEmployment($payroll);
        $advance = (int) round(PayrollReportBuilder::salaryAdvanceAmount($payroll));
        $deductions = $bpjsHealth
            + $bpjsEmployment
            + (int) $payroll->uniform_amount
            + (int) $payroll->equipment_amount
            + (int) $payroll->meal_amount
            + $advance
            + (int) $payroll->correction_minus;
        $correctionPlus = (int) $payroll->correction_plus;

        return [
            'gross' => (int) $payroll->gross_salary,
            'deductions' => $deductions,
            'correctionPlus' => $correctionPlus,
            'net' => (int) round((float) $payroll->gross_salary - $deductions + $correctionPlus),
            'bpjsHealth' => $bpjsHealth,
            'bpjsEmployment' => $bpjsEmployment,
            'advance' => $advance,
        ];
    }

    /**
     * This employee's total output per day for the last 7 days, zero-filled
     * for days with no realization, for the "Tren output" mini chart.
     *
     * @return array<int, array{label: string, value: float}>
     */
    private function outputTrend(Builder $own): array
    {
        $totals = (clone $own)
            ->selectRaw('work_date, SUM(total_output) as total')
            ->whereBetween('work_date', [today()->subDays(6)->toDateString(), today()->toDateString()])
            ->groupBy('work_date')
            ->pluck('total', 'work_date');

        return collect(range(6, 0))->map(function (int $daysAgo) use ($totals): array {
            $date = today()->subDays($daysAgo);

            return ['label' => $date->translatedFormat('d/m'), 'value' => (float) ($totals[$date->toDateString()] ?? 0)];
        })->all();
    }
}
