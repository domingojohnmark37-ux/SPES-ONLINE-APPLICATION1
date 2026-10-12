<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demographic_report_archives', function (Blueprint $table) {
            $table->id();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedSmallInteger('program_year');
            $table->unsignedInteger('version');
            $table->unsignedInteger('total_received');
            $table->unsignedInteger('outside_date_range');
            $table->unsignedInteger('not_approved');
            $table->unsignedInteger('duplicates_removed');
            $table->unsignedInteger('total_valid');
            $table->string('requested_by')->nullable();
            $table->string('status')->default('Draft');
            $table->string('docx_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->json('report_data');
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['start_date', 'end_date', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demographic_report_archives');
    }
};
