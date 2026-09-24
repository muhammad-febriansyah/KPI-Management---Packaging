<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDeduction;
use App\Models\Product;
use App\Models\User;
use App\Models\WorkRealization;
use App\Services\CurrentClientService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, CurrentClientService $currentClients): View
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

        if ($currentClient !== null && $user->roleCodeFor($currentClient) === 'employee') {
            return $this->employeeDashboard($user, $currentClient, $availableClients);
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
        ]);
    }

    private function employeeDashboard(User $user, mixed $currentClient, mixed $availableClients): View
    {
        $own = WorkRealization::where('client_id', $currentClient->id)
            ->where(function ($query) use ($user): void {
                $query->where('created_by', $user->id)
                    ->orWhereHas('employeeAssignments.employee', fn ($employeeQuery) => $employeeQuery->where('user_id', $user->id));
            });

        return view('dashboard-employee', [
            'availableClients' => $availableClients,
            'currentClient' => $currentClient,
            'user' => $user,
            'metrics' => [
                'realizationsToday' => (clone $own)->where('work_date', today()->toDateString())->count(),
                'outputToday' => (clone $own)->where('work_date', today()->toDateString())->sum('total_output'),
                'realizationsThisMonth' => (clone $own)->whereBetween('work_date', [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()])->count(),
                'realizationsTotal' => (clone $own)->count(),
            ],
            'recentRealizations' => (clone $own)->with(['shift', 'product'])->latest('work_date')->latest('id')->limit(8)->get(),
            'outputTrend' => $this->outputTrend($own),
            'deduction' => $user->employee ? EmployeeDeduction::query()->where('employee_id', $user->employee->id)
                ->whereHas('deductionPeriod', fn ($query) => $query->whereBetween('month', [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()]))
                ->first() : null,
        ]);
    }

    /**
     * This employee's total output per day for the last 7 days, zero-filled
     * for days with no realization, for the "Tren output" mini chart.
     *
     * @return array<int, array{label: string, value: float}>
     */
    private function outputTrend(mixed $own): array
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
