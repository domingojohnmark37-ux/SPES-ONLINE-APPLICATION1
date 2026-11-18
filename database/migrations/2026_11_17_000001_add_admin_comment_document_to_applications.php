<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->timestamp('review_started_at')->nullable()->after('status');
            $table->string('admin_comment_document', 40)->nullable()->after('admin_comment');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropColumn(['review_started_at', 'admin_comment_document']);
        });
    }
};
