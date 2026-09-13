<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('mother_name')->nullable()->change();
            $table->string('mother_occupation')->nullable()->change();
            $table->string('mother_contact_no')->nullable()->change();
            $table->string('father_guardian_name')->nullable()->change();
            $table->string('father_occupation')->nullable()->change();
            $table->string('father_contact_no')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('mother_name')->nullable(false)->change();
            $table->string('mother_occupation')->nullable(false)->change();
            $table->string('mother_contact_no')->nullable(false)->change();
            $table->string('father_guardian_name')->nullable(false)->change();
            $table->string('father_occupation')->nullable(false)->change();
            $table->string('father_contact_no')->nullable(false)->change();
        });
    }
};
