<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('target_audience', 32)->default('all_applicants');
            $table->unsignedBigInteger('target_user_id')->nullable();
            $table->foreign('target_user_id', 'appt_target_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign('appt_target_user_fk');
            $table->dropColumn(['target_audience', 'target_user_id']);
        });
    }
};
