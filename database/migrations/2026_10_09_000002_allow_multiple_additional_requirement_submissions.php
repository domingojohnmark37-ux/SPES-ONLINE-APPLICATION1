<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('application_additional_requirements', 'file_number')) {
            Schema::table('application_additional_requirements', function (Blueprint $table): void {
                $table->unsignedSmallInteger('file_number')->default(1)->after('additional_requirement_id');
            });
        }

        $indexes = Schema::getIndexes('application_additional_requirements');
        $previousUniqueIndexes = array_values(array_filter(
            $indexes,
            fn (array $index): bool => $index['unique']
                && $index['columns'] === ['application_id', 'additional_requirement_id'],
        ));
        foreach ($previousUniqueIndexes as $index) {
            Schema::table('application_additional_requirements', function (Blueprint $table) use ($index): void {
                $table->dropUnique($index['name']);
            });
        }

        $hasFileNumberUniqueIndex = collect($indexes)->contains(
            fn (array $index): bool => $index['unique']
                && $index['columns'] === ['application_id', 'additional_requirement_id', 'file_number'],
        );
        if (! $hasFileNumberUniqueIndex) {
            Schema::table('application_additional_requirements', function (Blueprint $table): void {
                $table->unique(
                    ['application_id', 'additional_requirement_id', 'file_number'],
                    'application_requirement_file_unique',
                );
            });
        }
    }

    public function down(): void
    {
        $hasDuplicateFileNumbers = DB::table('application_additional_requirements')
            ->select('application_id', 'additional_requirement_id')
            ->groupBy('application_id', 'additional_requirement_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateFileNumbers) {
            throw new \RuntimeException('Cannot restore the one-file-per-requirement constraint while multiple submissions exist.');
        }

        Schema::table('application_additional_requirements', function (Blueprint $table): void {
            $table->dropUnique('application_requirement_file_unique');
            $table->dropColumn('file_number');
            $table->unique(
                ['application_id', 'additional_requirement_id'],
                'application_requirement_unique',
            );
        });
    }
};
