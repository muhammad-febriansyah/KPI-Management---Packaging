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
        Schema::table('employees', function (Blueprint $table): void {
            $table->string('employee_no', 20)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('employees')->whereRaw('CHAR_LENGTH(employee_no) > 9')->exists()) {
            throw new RuntimeException('Cannot shorten employee numbers while generated IDs exist.');
        }

        Schema::table('employees', function (Blueprint $table): void {
            $table->string('employee_no', 9)->change();
        });
    }
};
