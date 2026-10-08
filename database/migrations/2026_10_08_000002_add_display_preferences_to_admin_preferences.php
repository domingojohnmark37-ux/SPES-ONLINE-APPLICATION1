<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_preferences', function (Blueprint $table): void {
            $table->string('language', 5)->default('en');
            $table->string('sidebar_behavior', 12)->default('auto');
            $table->string('font_size', 12)->default('medium');
        });
    }

    public function down(): void
    {
        Schema::table('admin_preferences', function (Blueprint $table): void {
            $table->dropColumn(['language', 'sidebar_behavior', 'font_size']);
        });
    }
};
