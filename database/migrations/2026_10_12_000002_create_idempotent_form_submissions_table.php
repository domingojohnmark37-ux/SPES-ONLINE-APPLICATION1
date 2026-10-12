<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotent_form_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('route_name', 120);
            $table->char('token_hash', 64);
            $table->text('redirect_url');
            $table->timestamps();
            $table->unique(['user_id', 'route_name', 'token_hash'], 'idempotent_form_submissions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotent_form_submissions');
    }
};
