<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('additional_requirement_templates')) {
            Schema::create('additional_requirement_templates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('additional_requirement_id');
                $table->string('file_path');
                $table->string('original_name');
                $table->timestamps();
            });
        }

        Schema::table('additional_requirement_templates', function (Blueprint $table) {
            $table->foreign('additional_requirement_id', 'add_req_tpl_req_fk')
                ->references('id')
                ->on('additional_requirements')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('additional_requirement_templates');
    }
};
