<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('news') && !Schema::hasColumn('news', 'display_on')) {
            Schema::table('news', function (Blueprint $table) {
                $table->string('display_on')->default('both')->after('content');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('news') && Schema::hasColumn('news', 'display_on')) {
            Schema::table('news', function (Blueprint $table) {
                $table->dropColumn('display_on');
            });
        }
    }
};
