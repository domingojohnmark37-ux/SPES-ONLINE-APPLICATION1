<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_backup_operations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('action', 20);
            $table->string('status', 20)->default('queued')->index();
            $table->string('archive_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('archive_type', 20)->nullable();
            $table->unsignedSmallInteger('archive_year')->nullable();
            $table->json('result')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_backup_operations');
    }
};
