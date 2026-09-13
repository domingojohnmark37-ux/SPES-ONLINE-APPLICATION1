<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE applications MODIFY parent_status ENUM('Both Parents', 'Both Parents Living Together', 'Single Parent', 'Orphan', 'Guardian') NOT NULL");
        }

        DB::table('applications')
            ->where('parent_status', 'Both Parents')
            ->update(['parent_status' => 'Both Parents Living Together']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE applications MODIFY parent_status ENUM('Both Parents Living Together', 'Single Parent', 'Orphan', 'Guardian') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE applications MODIFY parent_status ENUM('Both Parents', 'Both Parents Living Together', 'Single Parent', 'Orphan', 'Guardian') NOT NULL");
        }

        DB::table('applications')
            ->where('parent_status', 'Both Parents Living Together')
            ->update(['parent_status' => 'Both Parents']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE applications MODIFY parent_status ENUM('Both Parents', 'Single Parent', 'Orphan', 'Guardian') NOT NULL");
        }
    }
};
