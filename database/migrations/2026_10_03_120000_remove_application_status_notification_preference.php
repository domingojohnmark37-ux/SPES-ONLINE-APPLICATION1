<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('applicant_settings', 'notify_application_status')) {
            Schema::table('applicant_settings', function (Blueprint $table) {
                $table->dropColumn('notify_application_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('applicant_settings') && ! Schema::hasColumn('applicant_settings', 'notify_application_status')) {
            Schema::table('applicant_settings', function (Blueprint $table) {
                $table->boolean('notify_application_status')->default(true);
            });
        }
    }
};
