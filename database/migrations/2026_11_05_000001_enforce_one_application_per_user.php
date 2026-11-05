<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('application_duplicate_archives')) {
            Schema::create('application_duplicate_archives', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('application_id')->unique();
                $table->unsignedBigInteger('user_id');
                $table->longText('application_data');
                $table->timestamp('archived_at');
            });
        }

        $duplicateUserIds = DB::table('applications')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        foreach ($duplicateUserIds as $userId) {
            $duplicates = DB::table('applications')
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();

            foreach ($duplicates->skip(1) as $duplicate) {
                $archiveData = [
                    'application_id' => $duplicate->id,
                    'user_id' => $duplicate->user_id,
                    'application_data' => json_encode(
                        (array) $duplicate,
                        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                    ),
                    'archived_at' => now(),
                ];
                if (! DB::table('application_duplicate_archives')
                    ->where('application_id', $duplicate->id)
                    ->exists()) {
                    DB::table('application_duplicate_archives')->insert($archiveData);
                }
            }

            $duplicateIds = $duplicates->skip(1)->pluck('id');
            if ($duplicateIds->isNotEmpty()) {
                DB::table('applications')->whereIn('id', $duplicateIds)->delete();
            }
        }

        Schema::table('applications', function (Blueprint $table): void {
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table): void {
            $table->dropUnique('applications_user_id_unique');
        });

        // Retain archived rows so they remain recoverable after rolling back.
    }
};
