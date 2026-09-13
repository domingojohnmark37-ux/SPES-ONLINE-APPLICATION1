<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE applications MODIFY parent_status ENUM('Both Parents', 'Both Parents Living Together', 'Both Parents Living', 'Single Parent', 'Solo Parent', 'Orphan', 'Guardian') NOT NULL");
        }

        DB::table('applications')->where('parent_status', 'Both Parents')->update(['parent_status' => 'Both Parents Living']);
        DB::table('applications')->where('parent_status', 'Both Parents Living Together')->update(['parent_status' => 'Both Parents Living']);
        DB::table('applications')->where('parent_status', 'Single Parent')->update(['parent_status' => 'Solo Parent']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE applications MODIFY parent_status ENUM('Both Parents Living', 'Solo Parent', 'Orphan', 'Guardian') NOT NULL");
        }

        Schema::table('applications', function ($table) {
            $table->string('mother_occupation')->nullable()->change();
            $table->string('father_occupation')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE applications MODIFY parent_status ENUM('Both Parents Living', 'Solo Parent', 'Single Parent', 'Orphan', 'Guardian') NOT NULL");
        }

        DB::table('applications')->where('parent_status', 'Both Parents Living')->update(['parent_status' => 'Both Parents']);
        DB::table('applications')->where('parent_status', 'Solo Parent')->update(['parent_status' => 'Single Parent']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE applications MODIFY parent_status ENUM('Both Parents', 'Single Parent', 'Orphan', 'Guardian') NOT NULL");
        }

        Schema::table('applications', function ($table) {
            $table->string('mother_occupation')->nullable(false)->change();
            $table->string('father_occupation')->nullable(false)->change();
        });
    }
};