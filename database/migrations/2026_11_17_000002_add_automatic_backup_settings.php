<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->boolean('backup_enabled')->default(true);
            $table->string('backup_frequency', 10)->default('daily');
            $table->string('backup_time', 5)->default('02:00');
            $table->unsignedSmallInteger('backup_retention_days')->default(7);
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'backup_enabled',
                'backup_frequency',
                'backup_time',
                'backup_retention_days',
            ]);
        });
    }
};
