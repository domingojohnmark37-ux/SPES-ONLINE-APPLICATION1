<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('applications', 'resume') && ! Schema::hasColumn('applications', 'birth_certificate')) {
            Schema::table('applications', function ($table): void {
                $table->renameColumn('resume', 'birth_certificate');
            });
        }

        $this->renameDocumentOriginalNamesKey('resume', 'birth_certificate');
        $this->renameStoredPaths('birth_certificate', 'applications/resumes/', 'applications/birth_certificates/');
        $this->renameDocumentKeyColumns('resume', 'birth_certificate');
    }

    public function down(): void
    {
        $this->renameStoredPaths('birth_certificate', 'applications/birth_certificates/', 'applications/resumes/');
        $this->renameDocumentOriginalNamesKey('birth_certificate', 'resume');
        $this->renameDocumentKeyColumns('birth_certificate', 'resume');

        if (Schema::hasColumn('applications', 'birth_certificate') && ! Schema::hasColumn('applications', 'resume')) {
            Schema::table('applications', function ($table): void {
                $table->renameColumn('birth_certificate', 'resume');
            });
        }
    }

    private function renameStoredPaths(string $column, string $sourceDirectory, string $targetDirectory): void
    {
        if (! Schema::hasTable('applications') || ! Schema::hasColumn('applications', $column)) {
            return;
        }

        $disk = Storage::disk('public');

        DB::table('applications')
            ->select(['id', $column])
            ->whereNotNull($column)
            ->orderBy('id')
            ->each(function (object $application) use ($disk, $column, $sourceDirectory, $targetDirectory): void {
                $oldPath = (string) $application->{$column};
                if (! str_starts_with($oldPath, $sourceDirectory)) {
                    return;
                }

                $newPath = $targetDirectory.substr($oldPath, strlen($sourceDirectory));
                if ($disk->exists($oldPath)) {
                    if ($disk->exists($newPath)) {
                        Log::warning('Birth Certificate migration found an existing destination file; leaving the source file untouched.', [
                            'application_id' => $application->id,
                            'source' => $oldPath,
                            'destination' => $newPath,
                        ]);
                    } elseif (! $disk->move($oldPath, $newPath)) {
                        throw new \RuntimeException("Unable to move application document [{$oldPath}] to [{$newPath}].");
                    }
                } else {
                    Log::warning('Birth Certificate migration could not find the stored document file.', [
                        'application_id' => $application->id,
                        'path' => $oldPath,
                    ]);
                }

                DB::table('applications')->where('id', $application->id)->update([$column => $newPath]);
            });
    }

    private function renameDocumentOriginalNamesKey(string $fromKey, string $toKey): void
    {
        if (! Schema::hasTable('applications') || ! Schema::hasColumn('applications', 'document_original_names')) {
            return;
        }

        DB::table('applications')
            ->select(['id', 'document_original_names'])
            ->whereNotNull('document_original_names')
            ->orderBy('id')
            ->each(function (object $application) use ($fromKey, $toKey): void {
                $names = json_decode((string) $application->document_original_names, true);
                if (! is_array($names) || ! array_key_exists($fromKey, $names)) {
                    return;
                }

                if (! array_key_exists($toKey, $names)) {
                    $names[$toKey] = $names[$fromKey];
                }
                unset($names[$fromKey]);

                DB::table('applications')->where('id', $application->id)->update([
                    'document_original_names' => json_encode($names, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ]);
            });
    }

    private function renameDocumentKeyColumns(string $fromKey, string $toKey): void
    {
        $documentKeyColumns = ['field_name', 'document', 'document_key', 'document_field'];

        foreach (Schema::getTables() as $table) {
            $tableName = $table['name'];
            if ($tableName === 'migrations') {
                continue;
            }

            foreach (Schema::getColumnListing($tableName) as $column) {
                if (in_array($column, $documentKeyColumns, true)) {
                    DB::table($tableName)->where($column, $fromKey)->update([$column => $toKey]);
                }
            }
        }
    }
};
