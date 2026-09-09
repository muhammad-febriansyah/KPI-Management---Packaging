<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('deduction_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id');
            $table->date('month');
            $table->unsignedTinyInteger('week_no')->nullable();
            $table->unsignedTinyInteger('week_key')->storedAs('COALESCE(`week_no`, 0)');
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('locked_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'id']);
            $table->unique(['client_id', 'month', 'week_key']);
            $table->index(['client_id', 'month', 'status']);

            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
        });

        Schema::create('employee_deductions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id');
            $table->foreignId('deduction_period_id');
            $table->foreignId('employee_id');
            $table->unsignedBigInteger('uniform_amount')->default(0);
            $table->unsignedBigInteger('equipment_amount')->default(0);
            $table->unsignedBigInteger('meal_amount')->default(0);
            $table->decimal('bpjs_health_percent', 8, 3)->default(0);
            $table->decimal('bpjs_employment_percent', 8, 3)->default(0);
            $table->string('salary_advance_type', 20)->nullable();
            $table->decimal('salary_advance_value', 18, 3)->default(0);
            $table->unsignedBigInteger('correction_minus')->default(0);
            $table->unsignedBigInteger('correction_plus')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['deduction_period_id', 'employee_id']);
            $table->index(['employee_id', 'deduction_period_id']);

            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->foreign(['client_id', 'deduction_period_id'], 'employee_deductions_period_foreign')
                ->references(['client_id', 'id'])
                ->on('deduction_periods')
                ->cascadeOnDelete();
            $table->foreign(['client_id', 'employee_id'], 'employee_deductions_employee_foreign')
                ->references(['client_id', 'id'])
                ->on('employees')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_deductions');
        Schema::dropIfExists('deduction_periods');
    }
};
