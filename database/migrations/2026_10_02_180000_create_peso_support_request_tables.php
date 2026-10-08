<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('users')->cascadeOnDelete();
            $table->string('request_no')->nullable()->unique();
            $table->string('concern_type', 50);
            $table->string('subject', 150);
            $table->text('message');
            $table->string('status', 30)->default('open');
            $table->string('attachment_path')->nullable();
            $table->timestamps();
            $table->index(['applicant_id', 'created_at']);
        });

        Schema::create('support_request_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sender_type', 20);
            $table->text('message');
            $table->timestamps();
            $table->index(['support_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_request_messages');
        Schema::dropIfExists('support_requests');
    }
};
