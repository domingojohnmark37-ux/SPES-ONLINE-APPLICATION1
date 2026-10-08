<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('notify_application_status')->default(true);
            $table->boolean('notify_appointments')->default(true);
            $table->boolean('notify_announcements')->default(true);
            $table->boolean('notify_documents')->default(true);
            $table->string('notification_method')->default('in_app');
            $table->string('appearance')->default('system');
            $table->string('language')->default('en');
            $table->timestamps();
        });

        Schema::create('applicant_login_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('logged_in_at')->index();
            $table->timestamps();
            $table->index(['user_id', 'logged_in_at']);
        });

        Schema::create('applicant_support_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject', 120);
            $table->string('category', 40);
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('pending_email_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('email')->index();
            $table->string('pin_hash');
            $table->timestamp('pin_expires_at');
            $table->unsignedTinyInteger('failed_attempts')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_email_changes');
        Schema::dropIfExists('applicant_support_requests');
        Schema::dropIfExists('applicant_login_activities');
        Schema::dropIfExists('applicant_settings');
    }
};
