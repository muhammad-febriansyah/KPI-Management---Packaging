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
        Schema::create('groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('code', 50)->nullable();
            $table->string('name', 100);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['client_id', 'id']);
            $table->unique(['client_id', 'code']);
            $table->index(['client_id', 'status']);
        });

        Schema::create('units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name', 50);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['client_id', 'id']);
            $table->unique(['client_id', 'code']);
            $table->index(['client_id', 'status']);
        });

        Schema::create('cost_centers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name', 100);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['client_id', 'id']);
            $table->unique(['client_id', 'code']);
            $table->index(['client_id', 'status']);
        });

        Schema::create('shifts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name', 100);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['client_id', 'id']);
            $table->unique(['client_id', 'code']);
            $table->index(['client_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('cost_centers');
        Schema::dropIfExists('units');
        Schema::dropIfExists('groups');
    }
};
