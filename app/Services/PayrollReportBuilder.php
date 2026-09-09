<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PayrollReportBuilder
{
    /**
     * Build the per-employee payroll query for a client and date range.
     *
     * Attendance/gross salary and deductions are pre-aggregated in subqueries
     * so each is filtered to the given range before joining onto employees —
     * a left join straight onto realization_employees would still sum rows
     * outside the range, since only the work_realizations columns go null.
     */
    public function forClient(int $clientId, string $dateFrom, string $dateTo): Builder
    {
        $periodFrom = substr($dateFrom, 0, 7).'-01';
        $periodTo = substr($dateTo, 0, 7).'-01';

        $deductions = DB::table('employee_deductions')
            ->join('deduction_periods', 'deduction_periods.id', '=', 'employee_deductions.deduction_period_id')
            ->where('employee_deductions.client_id', $clientId)
            ->whereBetween('deduction_periods.month', [$periodFrom, $periodTo])
            ->select([
                'employee_deductions.employee_id',
                DB::raw('SUM(employee_deductions.uniform_amount) AS uniform_amount'),
                DB::raw('SUM(employee_deductions.equipment_amount) AS equipment_amount'),
                DB::raw('SUM(employee_deductions.meal_amount) AS meal_amount'),
                DB::raw('MAX(employee_deductions.bpjs_employment_percent) AS bpjs_employment_percent'),
                DB::raw('MAX(employee_deductions.salary_advance_type) AS salary_advance_type'),
                DB::raw('SUM(employee_deductions.salary_advance_value) AS salary_advance_value'),
                DB::raw('SUM(employee_deductions.correction_minus) AS correction_minus'),
                DB::raw('SUM(employee_deductions.correction_plus) AS correction_plus'),
            ])
            ->groupBy('employee_deductions.employee_id');

        $attendance = DB::table('realization_employees')
            ->join('work_realizations', function ($join) use ($clientId, $dateFrom, $dateTo): void {
                $join->on('work_realizations.id', '=', 'realization_employees.work_realization_id')
                    ->where('work_realizations.client_id', $clientId)
                    ->whereBetween('work_realizations.work_date', [$dateFrom, $dateTo]);
            })
            ->select([
                'realization_employees.employee_id',
                DB::raw('COUNT(DISTINCT work_realizations.work_date) AS attendance_days'),
                DB::raw('SUM(realization_employees.gross_amount) AS gross_salary'),
            ])
            ->groupBy('realization_employees.employee_id');

        return Employee::query()
            ->where('employees.client_id', $clientId)
            ->leftJoinSub($attendance, 'attendance', 'attendance.employee_id', '=', 'employees.id')
            ->leftJoinSub($deductions, 'deductions', 'deductions.employee_id', '=', 'employees.id')
            ->select([
                'employees.id',
                'employees.employee_no',
                'employees.full_name',
                'employees.gender',
                DB::raw('COALESCE(attendance.attendance_days, 0) AS attendance_days'),
                DB::raw('COALESCE(attendance.gross_salary, 0) AS gross_salary'),
                DB::raw('COALESCE(deductions.bpjs_employment_percent, 0) AS bpjs_employment_percent'),
                'deductions.salary_advance_type',
                DB::raw('COALESCE(deductions.uniform_amount, 0) AS uniform_amount'),
                DB::raw('COALESCE(deductions.equipment_amount, 0) AS equipment_amount'),
                DB::raw('COALESCE(deductions.meal_amount, 0) AS meal_amount'),
                DB::raw('COALESCE(deductions.salary_advance_value, 0) AS salary_advance_value'),
                DB::raw('COALESCE(deductions.correction_minus, 0) AS correction_minus'),
                DB::raw('COALESCE(deductions.correction_plus, 0) AS correction_plus'),
            ])
            ->orderBy('employees.full_name');
    }

    public static function bpjsEmployment(object $row): int
    {
        return (int) round(((float) $row->gross_salary * (float) $row->bpjs_employment_percent) / 100);
    }

    public static function salaryAdvanceAmount(object $row): float
    {
        return $row->salary_advance_type === 'percentage'
            ? ((float) $row->gross_salary * (float) $row->salary_advance_value) / 100
            : (float) $row->salary_advance_value;
    }

    public static function netSalary(object $row): int
    {
        $advance = self::salaryAdvanceAmount($row);

        return (int) round((float) $row->gross_salary - self::bpjsEmployment($row) - $row->uniform_amount - $row->equipment_amount - $row->meal_amount - $advance - $row->correction_minus + $row->correction_plus);
    }
}
