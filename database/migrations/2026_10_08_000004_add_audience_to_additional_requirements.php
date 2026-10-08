<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('additional_requirements', function (Blueprint $table) {
            $table->string('audience')->default('approved_applicants');
        });
    }

    public function down(): void
    {
        Schema::table('additional_requirements', function (Blueprint $table) {
            $table->dropColumn('audience');
        });
    }
};
