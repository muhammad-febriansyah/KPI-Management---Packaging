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
        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('employee_rate', 18, 3)->default(0)->after('po_price');
        });

        DB::statement('UPDATE products SET employee_rate = CASE WHEN COALESCE(old_employee_rate, 0) >= COALESCE(new_employee_rate, 0) THEN COALESCE(old_employee_rate, 0) ELSE COALESCE(new_employee_rate, 0) END');

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn(['old_employee_rate', 'new_employee_rate']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('old_employee_rate', 18, 3)->default(0)->after('po_price');
            $table->decimal('new_employee_rate', 18, 3)->default(0)->after('old_employee_rate');
        });

        DB::statement('UPDATE products SET old_employee_rate = employee_rate, new_employee_rate = employee_rate');

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('employee_rate');
        });
    }
};
