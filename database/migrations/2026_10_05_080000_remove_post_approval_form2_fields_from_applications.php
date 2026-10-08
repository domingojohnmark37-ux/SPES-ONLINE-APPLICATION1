<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FORM_2_COLUMNS = [
        'f2_control_no',
        'f2_place_of_birth',
        'f2_citizenship',
        'f2_email',
        'f2_social_media',
        'f2_gsis_beneficiary',
        'f2_present_address',
        'f2_permanent_address',
        'f2_applicant_category',
        'f2_special_skills',
        'f2_education_history',
        'f2_father_occupation',
        'f2_mother_occupation',
        'f2_spes_history',
        'f2_consent_accepted',
        'f2_checklist',
        'f2_parent_status_details',
        'f2_other_info',
        'forms_step',
    ];

    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(self::FORM_2_COLUMNS);
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('f2_control_no')->nullable();
            $table->string('f2_place_of_birth')->nullable();
            $table->string('f2_citizenship')->nullable();
            $table->string('f2_email')->nullable();
            $table->string('f2_social_media')->nullable();
            $table->string('f2_gsis_beneficiary')->nullable();
            $table->string('f2_present_address')->nullable();
            $table->string('f2_permanent_address')->nullable();
            $table->string('f2_applicant_category')->nullable();
            $table->string('f2_special_skills')->nullable();
            $table->json('f2_education_history')->nullable();
            $table->string('f2_father_occupation')->nullable();
            $table->string('f2_mother_occupation')->nullable();
            $table->json('f2_spes_history')->nullable();
            $table->boolean('f2_consent_accepted')->default(false);
            $table->json('f2_checklist')->nullable();
            $table->string('f2_parent_status_details')->nullable();
            $table->text('f2_other_info')->nullable();
            $table->unsignedTinyInteger('forms_step')->default(0);
        });
    }
};
