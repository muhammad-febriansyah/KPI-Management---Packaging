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
        Schema::create('work_realizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id');
            $table->date('work_date');
            $table->foreignId('shift_id');
            $table->foreignId('batch_id')->nullable();
            $table->foreignId('product_id');
            $table->string('sku_snapshot', 100);
            $table->string('product_name_snapshot', 180);
            $table->string('unit_name_snapshot', 50);
            $table->decimal('total_output', 18, 3);
            $table->time('start_time');
            $table->time('end_time');
            $table->text('report')->nullable();
            $table->boolean('is_complaint')->default(false);
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'id']);
            $table->index(['client_id', 'work_date']);
            $table->index(['client_id', 'work_date', 'status']);
            $table->index(['client_id', 'product_id', 'work_date']);
            $table->index(['client_id', 'shift_id', 'work_date']);
            $table->index(['client_id', 'is_complaint', 'work_date']);

            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->foreign(['client_id', 'shift_id'], 'realizations_client_shift_foreign')
                ->references(['client_id', 'id'])
                ->on('shifts')
                ->restrictOnDelete();
            $table->foreign(['client_id', 'batch_id'], 'realizations_client_batch_foreign')
                ->references(['client_id', 'id'])
                ->on('batches')
                ->restrictOnDelete();
            $table->foreign(['client_id', 'product_id'], 'realizations_client_product_foreign')
                ->references(['client_id', 'id'])
                ->on('products')
                ->restrictOnDelete();
        });

        Schema::create('realization_employees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id');
            $table->foreignId('work_realization_id');
            $table->foreignId('employee_id');
            $table->string('rate_category_snapshot', 10);
            $table->decimal('rate_per_unit_snapshot', 18, 3);
            $table->decimal('allocation_output', 18, 3)->nullable();
            $table->unsignedBigInteger('gross_amount')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->unique(['work_realization_id', 'employee_id']);
            $table->index(['employee_id', 'work_realization_id']);

            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->foreign(['client_id', 'work_realization_id'], 'realization_employees_work_foreign')
                ->references(['client_id', 'id'])
                ->on('work_realizations')
                ->cascadeOnDelete();
            $table->foreign(['client_id', 'employee_id'], 'realization_employees_employee_foreign')
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
        Schema::dropIfExists('realization_employees');
        Schema::dropIfExists('work_realizations');
    }
};
