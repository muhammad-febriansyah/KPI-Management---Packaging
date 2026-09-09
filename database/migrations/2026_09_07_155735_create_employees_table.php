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
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id');
            $table->string('employee_no', 9);
            $table->string('sim_id', 100)->nullable();
            $table->string('full_name', 150);
            $table->string('email', 150)->nullable();
            $table->string('phone', 30);
            $table->date('join_date');
            $table->string('gender', 20);
            $table->string('employee_status', 50);
            $table->string('marital_status', 30);
            $table->foreignId('group_id')->nullable();
            $table->string('rate_category', 10);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['client_id', 'id']);
            $table->unique(['client_id', 'employee_no']);
            $table->index(['client_id', 'sim_id']);
            $table->index(['client_id', 'full_name']);
            $table->index(['client_id', 'group_id', 'status']);
            $table->index(['client_id', 'join_date']);

            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->foreign(['client_id', 'group_id'], 'employees_client_group_foreign')
                ->references(['client_id', 'id'])
                ->on('groups')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
