<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('additional_requirements')) {
            Schema::create('additional_requirements', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150)->unique();
                $table->text('description')->nullable();
                $table->boolean('is_required')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('application_additional_requirements')) {
            Schema::create('application_additional_requirements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('application_id');
                $table->unsignedBigInteger('additional_requirement_id');
                $table->string('file_path');
                $table->string('original_name');
                $table->timestamps();

                $table->foreign('application_id', 'app_add_req_application_fk')
                    ->references('id')->on('applications')->cascadeOnDelete();
                $table->foreign('additional_requirement_id', 'app_add_req_requirement_fk')
                    ->references('id')->on('additional_requirements')->cascadeOnDelete();
                $table->unique(['application_id', 'additional_requirement_id'], 'application_requirement_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('application_additional_requirements');
        Schema::dropIfExists('additional_requirements');
    }
};
