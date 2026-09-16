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
        Schema::table('work_realizations', function (Blueprint $table): void {
            $table->string('result_image_path')->nullable()->after('report');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_realizations', function (Blueprint $table): void {
            $table->dropColumn('result_image_path');
        });
    }
};
