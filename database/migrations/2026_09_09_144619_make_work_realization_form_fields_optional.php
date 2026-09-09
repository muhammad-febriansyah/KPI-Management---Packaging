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
            $table->date('work_date')->nullable()->change();
            $table->foreignId('shift_id')->nullable()->change();
            $table->foreignId('product_id')->nullable()->change();
            $table->string('sku_snapshot', 100)->nullable()->change();
            $table->string('product_name_snapshot', 180)->nullable()->change();
            $table->string('unit_name_snapshot', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_realizations', function (Blueprint $table): void {
            $table->date('work_date')->nullable(false)->change();
            $table->foreignId('shift_id')->nullable(false)->change();
            $table->foreignId('product_id')->nullable(false)->change();
            $table->string('sku_snapshot', 100)->nullable(false)->change();
            $table->string('product_name_snapshot', 180)->nullable(false)->change();
            $table->string('unit_name_snapshot', 50)->nullable(false)->change();
        });
    }
};
