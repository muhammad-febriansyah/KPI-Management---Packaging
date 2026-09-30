<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize realization assignment output and gross amounts for existing rows.
     */
    public function up(): void
    {
        $realizationIds = DB::table('realization_employees')
            ->distinct()
            ->orderBy('work_realization_id')
            ->pluck('work_realization_id');

        foreach ($realizationIds as $realizationId) {
            $realization = DB::table('work_realizations')
                ->where('id', $realizationId)
                ->first(['total_output']);
            $assignments = DB::table('realization_employees')
                ->where('work_realization_id', $realizationId)
                ->orderBy('id')
                ->get(['id', 'rate_per_unit_snapshot']);
            $assignmentCount = $assignments->count();

            if ($realization === null || $assignmentCount === 0) {
                continue;
            }

            $totalOutput = $realization->total_output === null ? null : (float) $realization->total_output;
            $rate = (float) ($assignments->first()->rate_per_unit_snapshot ?? 0);
            $totalGrossAmount = $totalOutput === null ? 0 : (int) round($totalOutput * $rate);
            $sharedOutput = $totalOutput === null ? null : round($totalOutput / $assignmentCount, 3);
            $sharedGrossAmount = intdiv($totalGrossAmount, $assignmentCount);
            $grossRemainder = $totalGrossAmount % $assignmentCount;

            foreach ($assignments as $index => $assignment) {
                $isLastAssignment = $index === $assignmentCount - 1;

                DB::table('realization_employees')
                    ->where('id', $assignment->id)
                    ->update([
                        'allocation_output' => $sharedOutput === null || ! $isLastAssignment
                            ? $sharedOutput
                            : round($totalOutput - ($sharedOutput * ($assignmentCount - 1)), 3),
                        'gross_amount' => $sharedGrossAmount + ($isLastAssignment ? $grossRemainder : 0),
                    ]);
            }
        }
    }

    /**
     * Assignment amounts cannot be safely restored to their previous values.
     */
    public function down(): void {}
};
