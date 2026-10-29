<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Let a super admin assign employees from any client to a realization.
     */
    public function up(): void
    {
        Schema::table('realization_employees', function (Blueprint $table): void {
            $table->dropForeign('realization_employees_employee_foreign');
            $table->foreign('employee_id', 'realization_employees_employee_id_foreign')->references('id')->on('employees')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('realization_employees', function (Blueprint $table): void {
            $table->dropForeign('realization_employees_employee_id_foreign');
            $table->foreign(['client_id', 'employee_id'], 'realization_employees_employee_foreign')->references(['client_id', 'id'])->on('employees')->restrictOnDelete();
        });
    }
};
