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
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropForeign('employees_client_group_foreign');
            $table->foreign('group_id', 'employees_group_foreign')->references('id')->on('groups')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropForeign('employees_group_foreign');
            $table->foreign(['client_id', 'group_id'], 'employees_client_group_foreign')->references(['client_id', 'id'])->on('groups')->restrictOnDelete();
        });
    }
};
