<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('additional_requirements', function (Blueprint $table) {
            $table->string('template_path')->nullable();
            $table->string('template_original_name')->nullable();
            $table->dateTime('due_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('additional_requirements', function (Blueprint $table) {
            $table->dropColumn(['template_path', 'template_original_name', 'due_at']);
        });
    }
};
