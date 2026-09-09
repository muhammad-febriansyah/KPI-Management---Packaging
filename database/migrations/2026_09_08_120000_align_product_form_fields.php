<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('sku', 100)->nullable()->change();
            $table->string('name', 180)->nullable()->change();
            $table->foreignId('unit_id')->nullable()->change();
            $table->decimal('po_price', 18, 3)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('sku', 100)->nullable(false)->change();
            $table->string('name', 180)->nullable(false)->change();
            $table->foreignId('unit_id')->nullable(false)->change();
            $table->unsignedBigInteger('po_price')->default(0)->change();
        });
    }
};
