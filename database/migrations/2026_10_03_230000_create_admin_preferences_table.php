<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('theme', 10)->default('system');
            $table->string('date_format', 20)->default('M j, Y');
            $table->string('timezone', 64)->default('Asia/Manila');
            $table->timestamps();
        });

        Schema::table('system_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('admin_minimum_password_length')->default(12);
            $table->unsignedSmallInteger('admin_session_timeout_minutes')->default(30);
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['admin_minimum_password_length', 'admin_session_timeout_minutes']);
        });

        Schema::dropIfExists('admin_preferences');
    }
};
