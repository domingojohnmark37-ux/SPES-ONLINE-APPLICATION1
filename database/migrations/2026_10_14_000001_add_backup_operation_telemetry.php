<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_backup_operations', function (Blueprint $table): void {
            $table->dropForeign(['admin_id']);
            $table->unsignedBigInteger('admin_id')->nullable()->change();
            $table->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
        });

        Schema::table('backup_archive_credentials', function (Blueprint $table): void {
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('verified_at')->nullable();
        });

        Schema::create('backup_cleanup_runs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('examined')->default(0);
            $table->unsignedInteger('deleted')->default(0);
            $table->json('results');
            $table->timestamp('created_at')->useCurrent();
        });

        DB::table('admin_backup_operations')->where('status', 'done')->update(['status' => 'completed']);
        DB::table('admin_backup_operations')->where('status', 'running')->update(['status' => 'processing']);
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_cleanup_runs');

        Schema::table('backup_archive_credentials', function (Blueprint $table): void {
            $table->dropColumn(['file_size_bytes', 'duration_ms', 'verified_at']);
        });

        Schema::table('admin_backup_operations', function (Blueprint $table): void {
            $table->dropColumn(['file_size_bytes', 'duration_ms', 'started_at', 'completed_at', 'verified_at']);
            $table->dropForeign(['admin_id']);
            $table->unsignedBigInteger('admin_id')->nullable(false)->change();
            $table->foreign('admin_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
