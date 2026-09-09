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
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id');
            $table->string('sku', 100);
            $table->string('name', 180);
            $table->foreignId('unit_id');
            $table->foreignId('group_id')->nullable();
            $table->foreignId('cost_center_id')->nullable();
            $table->unsignedBigInteger('po_price')->default(0);
            $table->decimal('old_employee_rate', 18, 3)->default(0);
            $table->decimal('new_employee_rate', 18, 3)->default(0);
            $table->unsignedInteger('estimated_output_per_hour')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['client_id', 'id']);
            $table->unique(['client_id', 'sku']);
            $table->index(['client_id', 'name']);
            $table->index(['client_id', 'status']);
            $table->index(['client_id', 'group_id', 'status']);
            $table->index(['client_id', 'cost_center_id']);

            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->foreign(['client_id', 'unit_id'], 'products_client_unit_foreign')
                ->references(['client_id', 'id'])
                ->on('units')
                ->restrictOnDelete();
            $table->foreign(['client_id', 'group_id'], 'products_client_group_foreign')
                ->references(['client_id', 'id'])
                ->on('groups')
                ->restrictOnDelete();
            $table->foreign(['client_id', 'cost_center_id'], 'products_client_cost_center_foreign')
                ->references(['client_id', 'id'])
                ->on('cost_centers')
                ->restrictOnDelete();
        });

        Schema::create('batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id');
            $table->string('batch_no', 100);
            $table->foreignId('product_id')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['client_id', 'id']);
            $table->unique(['client_id', 'batch_no']);
            $table->index(['client_id', 'product_id', 'status']);

            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->foreign(['client_id', 'product_id'], 'batches_client_product_foreign')
                ->references(['client_id', 'id'])
                ->on('products')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
        Schema::dropIfExists('products');
    }
};
