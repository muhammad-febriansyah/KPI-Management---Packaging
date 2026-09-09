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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('name', 150)->change();
            $table->string('username', 100)->nullable()->unique()->after('name');
            $table->string('email', 150)->nullable()->change();
            $table->boolean('is_super_admin')->default(false)->after('password');
            $table->string('status', 20)->default('active')->index()->after('is_super_admin');
            $table->timestamp('last_login_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'username',
                'is_super_admin',
                'status',
                'last_login_at',
            ]);

            $table->string('name')->change();
            $table->string('email')->nullable(false)->change();
        });
    }
};
