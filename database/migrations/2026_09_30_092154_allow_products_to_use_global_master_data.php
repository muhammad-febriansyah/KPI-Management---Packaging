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
        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign('products_client_unit_foreign');
            $table->dropForeign('products_client_group_foreign');
            $table->dropForeign('products_client_cost_center_foreign');

            $table->foreign('unit_id', 'products_unit_foreign')->references('id')->on('units')->restrictOnDelete();
            $table->foreign('group_id', 'products_group_foreign')->references('id')->on('groups')->restrictOnDelete();
            $table->foreign('cost_center_id', 'products_cost_center_foreign')->references('id')->on('cost_centers')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign('products_unit_foreign');
            $table->dropForeign('products_group_foreign');
            $table->dropForeign('products_cost_center_foreign');

            $table->foreign(['client_id', 'unit_id'], 'products_client_unit_foreign')->references(['client_id', 'id'])->on('units')->restrictOnDelete();
            $table->foreign(['client_id', 'group_id'], 'products_client_group_foreign')->references(['client_id', 'id'])->on('groups')->restrictOnDelete();
            $table->foreign(['client_id', 'cost_center_id'], 'products_client_cost_center_foreign')->references(['client_id', 'id'])->on('cost_centers')->restrictOnDelete();
        });
    }
};
