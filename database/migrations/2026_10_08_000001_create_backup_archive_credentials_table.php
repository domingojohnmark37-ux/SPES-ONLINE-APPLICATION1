<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_archive_credentials', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->char('credential_hash', 64);
            $table->string('archive_type', 20);
            $table->unsignedSmallInteger('archive_year')->nullable();
            $table->unsignedBigInteger('generated_by')->nullable()->index();
            $table->string('archive_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_archive_credentials');
    }
};
