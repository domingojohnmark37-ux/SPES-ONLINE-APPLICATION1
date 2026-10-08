<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_lists', function (Blueprint $table): void {
            $table->string('archive_path')->nullable()->after('filters_json');
        });
    }

    public function down(): void
    {
        Schema::table('master_lists', function (Blueprint $table): void {
            $table->dropColumn('archive_path');
        });
    }
};
