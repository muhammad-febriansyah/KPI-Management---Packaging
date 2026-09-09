<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ([
            ['products', 'po_price'],
            ['realization_employees', 'gross_amount'],
            ['employee_deductions', 'uniform_amount'],
            ['employee_deductions', 'equipment_amount'],
            ['employee_deductions', 'meal_amount'],
            ['employee_deductions', 'correction_minus'],
            ['employee_deductions', 'correction_plus'],
        ] as [$table, $column]) {
            $this->ensureWholeRupiah($table, $column);
        }

        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedBigInteger('po_price')->default(0)->change();
        });

        Schema::table('realization_employees', function (Blueprint $table): void {
            $table->unsignedBigInteger('gross_amount')->default(0)->change();
        });

        Schema::table('employee_deductions', function (Blueprint $table): void {
            $table->unsignedBigInteger('uniform_amount')->default(0)->change();
            $table->unsignedBigInteger('equipment_amount')->default(0)->change();
            $table->unsignedBigInteger('meal_amount')->default(0)->change();
            $table->unsignedBigInteger('correction_minus')->default(0)->change();
            $table->unsignedBigInteger('correction_plus')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_deductions', function (Blueprint $table): void {
            $table->decimal('uniform_amount', 18, 3)->default(0)->change();
            $table->decimal('equipment_amount', 18, 3)->default(0)->change();
            $table->decimal('meal_amount', 18, 3)->default(0)->change();
            $table->decimal('correction_minus', 18, 3)->default(0)->change();
            $table->decimal('correction_plus', 18, 3)->default(0)->change();
        });

        Schema::table('realization_employees', function (Blueprint $table): void {
            $table->decimal('gross_amount', 18, 3)->default(0)->change();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('po_price', 18, 3)->default(0)->change();
        });
    }

    private function ensureWholeRupiah(string $table, string $column): void
    {
        if (DB::table($table)->whereRaw("`{$column}` <> FLOOR(`{$column}`)")->exists()) {
            throw new RuntimeException("{$table}.{$column} contains fractional values; normalize them before converting to BIGINT.");
        }
    }
};
