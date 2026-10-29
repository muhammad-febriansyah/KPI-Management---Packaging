<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Let a super admin record a realization on a shift owned by another client.
     */
    public function up(): void
    {
        Schema::table('work_realizations', function (Blueprint $table): void {
            $table->dropForeign('realizations_client_shift_foreign');
            $table->foreign('shift_id', 'realizations_shift_foreign')->references('id')->on('shifts')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_realizations', function (Blueprint $table): void {
            $table->dropForeign('realizations_shift_foreign');
            $table->foreign(['client_id', 'shift_id'], 'realizations_client_shift_foreign')->references(['client_id', 'id'])->on('shifts')->restrictOnDelete();
        });
    }
};
