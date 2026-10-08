<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PDO;
use RuntimeException;
use SplFileInfo;
use ZipArchive;

class AdminBackupService
{
    private const FORMAT_VERSION = 2;

    public function create(string $archiveType = 'manual', ?int $year = null, ?int $generatedBy = null): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP ZIP extension is required to create backups.');
        }
        if (! in_array($archiveType, ['manual', 'annual'], true)) {
            throw new RuntimeException('The backup archive type is invalid.');
        }
        if (! Schema::hasTable('backup_archive_credentials')) {
            throw new RuntimeException('Run the database migrations before creating a protected backup.');
        }

        $archiveId = (string) Str::uuid();
        $archivePath = tempnam(sys_get_temp_dir(), 'spes-backup-');
        $sqlPath = tempnam(sys_get_temp_dir(), 'spes-database-');
        $dataPath = tempnam(sys_get_temp_dir(), 'spes-database-data-');
        if ($archivePath === false || $sqlPath === false || $dataPath === false) {
            throw new RuntimeException('Unable to allocate temporary storage for the backup.');
        }

        try {
            DB::transaction(function () use ($sqlPath, $dataPath): void {
                $this->writeDatabaseDump($sqlPath);
                $this->writeDataSnapshot($dataPath);
            });
            $snapshotSize = filesize($dataPath);
            if ($snapshotSize === false || $snapshotSize > 268_435_456) {
                throw new RuntimeException('The database snapshot exceeds the supported 256 MB restore limit.');
            }
            $zip = new ZipArchive;
            if ($zip->open($archivePath, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Unable to create the backup archive.');
            }

            if (! $zip->addFile($sqlPath, 'database/backup.sql')) {
                $zip->close();
                throw new RuntimeException('Unable to add the database dump to the backup.');
            }
            if (! $zip->addFile($dataPath, 'database/data.json')) {
                $zip->close();
                throw new RuntimeException('Unable to add the database data snapshot to the backup.');
            }

            if (! $zip->addFromString(
                'README.txt',
                "SPES Online Application backup\n"
                ."Contains the application database, files from storage/app/private and storage/app/public, and application logs.\n"
                ."Restore merges records and files that do not already exist; it does not overwrite current records or files.\n"
                ."It does not contain environment files, credentials, or application source code.\n"
                ."Keep this archive in a secure location because it contains applicant information.\n",
            )) {
                $zip->close();
                throw new RuntimeException('Unable to add the backup information file.');
            }
            $this->addStorageFiles($zip);
            $this->addLogFiles($zip);
            if (! $zip->close()) {
                throw new RuntimeException('Unable to finalize the backup archive before verification.');
            }

            $zip = new ZipArchive;
            if ($zip->open($archivePath) !== true) {
                throw new RuntimeException('Unable to reopen the backup archive for integrity verification.');
            }
            $manifest = $this->buildManifest($zip, $archiveId, $archiveType, $year);
            if (! $zip->addFromString('manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT))) {
                $zip->close();
                throw new RuntimeException('Unable to add the backup integrity manifest.');
            }

            if (! $zip->close()) {
                throw new RuntimeException('Unable to finalize the backup archive.');
            }

            $this->registerCredential($manifest, $archiveType, $year, $generatedBy);

            return $archivePath;
        } catch (\Throwable $exception) {
            @unlink($archivePath);
            throw $exception;
        } finally {
            @unlink($sqlPath);
            @unlink($dataPath);
        }
    }

    public function restore(string $archivePath, ?string $expectedType = null, ?int $expectedYear = null): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP ZIP extension is required to restore backups.');
        }

        $zip = new ZipArchive;
        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('The uploaded file is not a readable backup archive.');
        }

        $createdFiles = [];
        try {
            $manifest = $this->validateArchive($zip);
            if (
                ($expectedType !== null && ($manifest['archive_type'] ?? null) !== $expectedType)
                || ($expectedYear !== null && ($manifest['archive_year'] ?? null) !== $expectedYear)
            ) {
                throw new RuntimeException('The backup credential does not match the selected annual archive.');
            }
            $snapshotContents = $zip->getFromName('database/data.json');
            if (! is_string($snapshotContents)) {
                throw new RuntimeException('The backup is missing its database data snapshot.');
            }

            try {
                $snapshot = json_decode($snapshotContents, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new RuntimeException('The backup database snapshot is invalid.', previous: $exception);
            }
            unset($snapshotContents);

            $tables = $this->validateSnapshot($snapshot);
            $this->restoreFiles($zip, $createdFiles);
            $importedRows = $this->mergeRows($tables, $manifest);

            return [
                'tables' => count($tables),
                'rows' => $importedRows,
                'files' => count($createdFiles),
                'manifest' => $manifest,
            ];
        } catch (\Throwable $exception) {
            $this->removeRestoredFiles($createdFiles);
            throw $exception;
        } finally {
            $zip->close();
        }
    }

    public function createAnnualArchive(int $year): ?array
    {
        if ($year < 2000 || $year > 9999) {
            throw new RuntimeException('The annual backup year is invalid.');
        }

        $filename = "spes-annual-backup-{$year}.zip";
        $archivePath = 'backups/annual/'.$filename;
        $disk = Storage::disk('local');

        if ($disk->exists($archivePath)) {
            return null;
        }

        $temporaryArchive = $this->create('annual', $year);
        $stream = fopen($temporaryArchive, 'rb');
        if ($stream === false) {
            @unlink($temporaryArchive);
            throw new RuntimeException('Unable to open the annual backup for archival.');
        }

        try {
            if (! $disk->writeStream($archivePath, $stream)) {
                throw new RuntimeException('Unable to save the annual backup to private archive storage.');
            }
        } finally {
            fclose($stream);
            @unlink($temporaryArchive);
        }
        $manifest = $this->readArchiveManifest($disk->path($archivePath));
        DB::table('backup_archive_credentials')
            ->where('id', $manifest['credential']['id'])
            ->update([
                'archive_path' => $archivePath,
                'updated_at' => now(),
            ]);

        return $this->annualArchive($year);
    }

    public function annualArchives(): array
    {
        $archives = [];
        foreach (Storage::disk('local')->files('backups/annual') as $path) {
            if (! preg_match('#^backups/annual/spes-annual-backup-(\d{4})\.zip$#D', $path, $matches)) {
                continue;
            }

            $archives[] = $this->annualArchive((int) $matches[1]);
        }

        usort($archives, static fn (array $first, array $second): int => $second['year'] <=> $first['year']);

        return $archives;
    }

    public function annualArchive(int $year): ?array
    {
        $path = "backups/annual/spes-annual-backup-{$year}.zip";
        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            return null;
        }

        return [
            'year' => $year,
            'path' => $path,
            'size' => $disk->size($path),
            'last_modified' => $disk->lastModified($path),
        ];
    }

    private function writeDatabaseDump(string $path): void
    {
        $pdo = DB::connection()->getPdo();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Unable to write the database dump.');
        }

        try {
            fwrite($handle, "-- SPES database backup\n");
            if ($driver === 'mysql') {
                fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
                $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            } elseif ($driver === 'sqlite') {
                fwrite($handle, "PRAGMA foreign_keys=OFF;\n");
                $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
            } else {
                throw new RuntimeException("Database backup does not support [{$driver}].");
            }

            while (($table = $tables->fetch(PDO::FETCH_NUM)) !== false) {
                $tableName = (string) $table[0];
                $identifier = $this->quoteIdentifier($tableName);
                $createStatement = $driver === 'mysql'
                    ? $pdo->query("SHOW CREATE TABLE {$identifier}")
                    : $pdo->prepare("SELECT sql FROM sqlite_master WHERE type='table' AND name=?");
                if ($driver === 'sqlite') {
                    $createStatement->execute([$tableName]);
                }
                $create = $createStatement->fetch(PDO::FETCH_NUM);
                $createSql = $driver === 'mysql' ? ($create[1] ?? null) : ($create[0] ?? null);
                if (! is_string($createSql) || $createSql === '') {
                    throw new RuntimeException("Unable to read the schema for table [{$tableName}].");
                }

                fwrite($handle, "\nDROP TABLE IF EXISTS {$identifier};\n{$createSql};\n");
                $rows = $pdo->query("SELECT * FROM {$identifier}");

                while (($row = $rows->fetch(PDO::FETCH_ASSOC)) !== false) {
                    $columns = array_map(fn (string $column): string => $this->quoteIdentifier($column), array_keys($row));
                    $values = array_map(fn ($value): string => $this->quoteValue($pdo, $value), array_values($row));
                    fwrite($handle, 'INSERT INTO '.$identifier.' ('.implode(', ', $columns).') VALUES ('.implode(', ', $values).");\n");
                }
            }

            fwrite($handle, $driver === 'mysql' ? "\nSET FOREIGN_KEY_CHECKS=1;\n" : "\nPRAGMA foreign_keys=ON;\n");
        } finally {
            fclose($handle);
        }
    }

    private function addStorageFiles(ZipArchive $zip): void
    {
        foreach (['local' => 'private', 'public' => 'public'] as $disk => $archiveDisk) {
            $storage = Storage::disk($disk);
            foreach ($storage->allFiles() as $relativePath) {
                if ($disk === 'local' && str_starts_with($relativePath, 'backups/annual/')) {
                    continue;
                }
                $filePath = $storage->path($relativePath);
                $file = new SplFileInfo($filePath);
                if (! $file->isFile() || $file->isLink()) {
                    continue;
                }

                $archiveName = 'uploads/'.$archiveDisk.'/'.str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
                if (! $zip->addFile($filePath, $archiveName)) {
                    throw new RuntimeException("Unable to add stored file [{$relativePath}] to the backup.");
                }
            }
        }
    }

    private function addLogFiles(ZipArchive $zip): void
    {
        $logRoot = storage_path('logs');
        if (! is_dir($logRoot)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($logRoot, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->isLink()) {
                continue;
            }

            $relativePath = substr($file->getPathname(), strlen($logRoot) + 1);
            $archiveName = 'logs/'.str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            if (! $zip->addFile($file->getPathname(), $archiveName)) {
                throw new RuntimeException("Unable to add application log [{$relativePath}] to the backup.");
            }
        }
    }

    private function writeDataSnapshot(string $path): void
    {
        $pdo = DB::connection()->getPdo();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $tables = $this->databaseTableNames($pdo, $driver);
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Unable to write the database data snapshot.');
        }

        try {
            fwrite($handle, '{"format":"spes-db-rows-v1","tables":{');
            foreach ($tables as $tableIndex => $tableName) {
                if ($tableIndex > 0) {
                    fwrite($handle, ',');
                }
                fwrite($handle, json_encode($tableName, JSON_THROW_ON_ERROR).':[');

                $rows = $pdo->query('SELECT * FROM '.$this->quoteIdentifier($tableName));
                $rowIndex = 0;
                while (($row = $rows->fetch(PDO::FETCH_ASSOC)) !== false) {
                    if ($rowIndex++ > 0) {
                        fwrite($handle, ',');
                    }
                    $encodedRow = [];
                    foreach ($row as $column => $value) {
                        $encodedRow[$column] = $this->encodeSnapshotValue($value);
                    }
                    fwrite($handle, json_encode($encodedRow, JSON_THROW_ON_ERROR));
                }

                fwrite($handle, ']');
            }
            fwrite($handle, '}}');
        } finally {
            fclose($handle);
        }
    }

    private function encodeSnapshotValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_match('//u', $value) === 1
            ? ['__spes_backup_type' => 'string', 'value' => $value]
            : ['__spes_backup_type' => 'binary', 'value' => base64_encode($value)];
    }

    private function decodeSnapshotValue(mixed $value): mixed
    {
        if (! is_array($value) || ! isset($value['__spes_backup_type'], $value['value'])) {
            return $value;
        }

        if ($value['__spes_backup_type'] === 'string' && is_string($value['value'])) {
            return $value['value'];
        }
        if ($value['__spes_backup_type'] === 'binary' && is_string($value['value'])) {
            $decoded = base64_decode($value['value'], true);
            if ($decoded !== false) {
                return $decoded;
            }
        }

        throw new RuntimeException('The backup contains a malformed database value.');
    }

    private function databaseTableNames(PDO $pdo, string $driver): array
    {
        if ($driver === 'mysql') {
            $statement = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        } elseif ($driver === 'sqlite') {
            $statement = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        } else {
            throw new RuntimeException("Database backup does not support [{$driver}].");
        }

        return array_map(
            static fn (array $table): string => (string) $table[0],
            $statement->fetchAll(PDO::FETCH_NUM),
        );
    }

    private function buildManifest(ZipArchive $zip, string $archiveId, string $archiveType, ?int $year): array
    {
        $hashes = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (! is_string($name) || $name === 'manifest.json' || str_ends_with($name, '/')) {
                continue;
            }

            $stream = $zip->getStream($name);
            if ($stream === false) {
                throw new RuntimeException("Unable to verify backup entry [{$name}].");
            }
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);
            fclose($stream);
            $hashes[$name] = hash_final($context);
        }

        ksort($hashes);

        $manifest = [
            'format' => self::FORMAT_VERSION,
            'created_at' => now()->toIso8601String(),
            'database_driver' => DB::connection()->getDriverName(),
            'archive_type' => $archiveType,
            'archive_year' => $year,
            'hashes' => $hashes,
        ];
        $manifest['credential'] = [
            'id' => $archiveId,
            'signature' => $this->signCredential($archiveId, $archiveType, $year, $hashes),
        ];

        return $manifest;
    }

    private function signCredential(string $archiveId, string $archiveType, ?int $year, array $hashes): string
    {
        ksort($hashes);
        $payload = json_encode([
            'version' => 1,
            'id' => $archiveId,
            'archive_type' => $archiveType,
            'archive_year' => $year,
            'hashes' => $hashes,
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return hash_hmac('sha256', $payload, $this->credentialSigningKey());
    }

    private function credentialSigningKey(): string
    {
        $applicationKey = (string) config('app.key');
        if (str_starts_with($applicationKey, 'base64:')) {
            $applicationKey = base64_decode(substr($applicationKey, 7), true) ?: '';
        }
        if ($applicationKey === '') {
            throw new RuntimeException('APP_KEY must be configured to issue or verify backup credentials.');
        }

        return hash_hmac('sha256', 'spes-backup-credential-v1', $applicationKey, true);
    }

    private function validateCredential(array $manifest): void
    {
        $credential = $manifest['credential'] ?? null;
        $archiveType = $manifest['archive_type'] ?? null;
        $year = $manifest['archive_year'] ?? null;
        $hashes = $manifest['hashes'] ?? null;
        if (
            ! is_array($credential)
            || ! is_string($credential['id'] ?? null)
            || ! preg_match('/^[0-9a-f-]{36}$/D', $credential['id'])
            || ! is_string($credential['signature'] ?? null)
            || ! preg_match('/^[a-f0-9]{64}$/D', $credential['signature'])
            || ! in_array($archiveType, ['manual', 'annual'], true)
            || ($archiveType === 'annual' && (! is_int($year) || $year < 2000 || $year > 9999))
            || ($archiveType === 'manual' && $year !== null)
            || ! is_array($hashes)
        ) {
            throw new RuntimeException('The backup credential is invalid or missing.');
        }

        $expectedSignature = $this->signCredential($credential['id'], $archiveType, $year, $hashes);
        if (! hash_equals($expectedSignature, $credential['signature'])) {
            throw new RuntimeException('The backup credential is not authorized or its contents have been modified.');
        }
        if (! Schema::hasTable('backup_archive_credentials')) {
            throw new RuntimeException('The backup credential registry is unavailable. Run the database migrations before restoring.');
        }

        $registered = DB::table('backup_archive_credentials')->where('id', $credential['id'])->first();
        if ($registered !== null && (
            ! hash_equals($registered->credential_hash, hash('sha256', $credential['signature']))
            || $registered->archive_type !== $archiveType
            || $registered->archive_year !== $year
        )) {
            throw new RuntimeException('The backup credential does not match the credential registered in this database.');
        }
    }

    private function registerCredential(array $manifest, string $archiveType, ?int $year, ?int $generatedBy): void
    {
        $credential = $manifest['credential'];
        DB::table('backup_archive_credentials')->insertOrIgnore([
            'id' => $credential['id'],
            'credential_hash' => hash('sha256', $credential['signature']),
            'archive_type' => $archiveType,
            'archive_year' => $year,
            'generated_by' => $generatedBy,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $registered = DB::table('backup_archive_credentials')->where('id', $credential['id'])->first();
        if ($registered === null || ! hash_equals($registered->credential_hash, hash('sha256', $credential['signature']))) {
            throw new RuntimeException('The backup credential could not be attached to the database registry.');
        }
    }

    private function readArchiveManifest(string $archivePath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($archivePath) !== true) {
            throw new RuntimeException('Unable to open the annual archive to register its credential.');
        }
        try {
            $contents = $zip->getFromName('manifest.json');
            if (! is_string($contents)) {
                throw new RuntimeException('The annual archive manifest is missing.');
            }

            return json_decode($contents, true, 32, JSON_THROW_ON_ERROR);
        } finally {
            $zip->close();
        }
    }

    private function validateArchive(ZipArchive $zip): array
    {
        if ($zip->numFiles < 2 || $zip->numFiles > 100_000) {
            throw new RuntimeException('The backup has an invalid number of files.');
        }

        $manifestContents = $zip->getFromName('manifest.json');
        if ($manifestContents === false) {
            throw new RuntimeException('This ZIP has no SPES backup credential and cannot be restored.');
        }
        if (! is_string($manifestContents) || strlen($manifestContents) > 5_000_000) {
            throw new RuntimeException('The backup integrity manifest is invalid.');
        }
        try {
            $manifest = json_decode($manifestContents, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException('The backup integrity manifest is invalid.', previous: $exception);
        }

        if (($manifest['format'] ?? null) !== self::FORMAT_VERSION || ! is_array($manifest['hashes'] ?? null)) {
            throw new RuntimeException('This backup format is not supported. Create a new backup with the current version.');
        }
        $this->validateCredential($manifest);
        if (! isset($manifest['hashes']['database/backup.sql'], $manifest['hashes']['database/data.json'])) {
            throw new RuntimeException('The backup is missing its database contents.');
        }

        $totalSize = 0;
        $seen = [];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            $stat = $zip->statIndex($index);
            if (! is_string($name) || ! is_array($stat) || str_ends_with($name, '/')) {
                throw new RuntimeException('The backup contains an invalid archive entry.');
            }
            if ($this->isUnsafeArchivePath($name) || isset($seen[$name])) {
                throw new RuntimeException('The backup contains a duplicate or unsafe file path.');
            }
            $seen[$name] = true;
            $operatingSystem = 0;
            $attributes = 0;
            $zip->getExternalAttributesIndex($index, $operatingSystem, $attributes);
            if (($operatingSystem === ZipArchive::OPSYS_UNIX) && ((($attributes >> 16) & 0170000) === 0120000)) {
                throw new RuntimeException('The backup contains a symbolic link, which cannot be restored.');
            }
            $totalSize += (int) ($stat['size'] ?? 0);
            if ($totalSize > 4_000_000_000) {
                throw new RuntimeException('The backup exceeds the maximum uncompressed archive size.');
            }
            if ($name === 'database/data.json' && (int) ($stat['size'] ?? 0) > 268_435_456) {
                throw new RuntimeException('The database snapshot exceeds the supported 256 MB restore limit.');
            }

            if ($name !== 'manifest.json' && ! isset($manifest['hashes'][$name])) {
                throw new RuntimeException('The backup contains a file that is not listed in its integrity manifest.');
            }
            if ($name === 'manifest.json') {
                continue;
            }
            if (! preg_match('/^[a-f0-9]{64}$/D', (string) ($manifest['hashes'][$name] ?? ''))) {
                throw new RuntimeException('The backup manifest contains an invalid checksum.');
            }

            $stream = $zip->getStream($name);
            if ($stream === false) {
                throw new RuntimeException("Unable to read backup entry [{$name}].");
            }
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);
            fclose($stream);
            if (! hash_equals($manifest['hashes'][$name], hash_final($context))) {
                throw new RuntimeException("Backup integrity check failed for [{$name}].");
            }
        }

        if (count($seen) !== count($manifest['hashes']) + 1) {
            throw new RuntimeException('The backup manifest does not match the archive contents.');
        }

        return $manifest;
    }

    private function validateLegacyArchive(ZipArchive $zip): array
    {
        if ($zip->numFiles < 2 || $zip->numFiles > 100_000) {
            throw new RuntimeException('The backup has an invalid number of files.');
        }

        $seen = [];
        $totalSize = 0;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            $stat = $zip->statIndex($index);
            if (! is_string($name) || ! is_array($stat) || str_ends_with($name, '/')
                || $this->isUnsafeLegacyArchivePath($name) || isset($seen[$name])) {
                throw new RuntimeException('The legacy backup contains an invalid or unsafe file path.');
            }
            $seen[$name] = true;
            $totalSize += (int) ($stat['size'] ?? 0);
            if ($totalSize > 4_000_000_000) {
                throw new RuntimeException('The backup exceeds the maximum uncompressed archive size.');
            }
            if ($name === 'database/backup.sql' && (int) ($stat['size'] ?? 0) > 268_435_456) {
                throw new RuntimeException('The legacy SQL dump exceeds the supported 256 MB restore limit.');
            }

            $operatingSystem = 0;
            $attributes = 0;
            $zip->getExternalAttributesIndex($index, $operatingSystem, $attributes);
            if ($operatingSystem === ZipArchive::OPSYS_UNIX && ((($attributes >> 16) & 0170000) === 0120000)) {
                throw new RuntimeException('The backup contains a symbolic link, which cannot be restored.');
            }
        }

        if (! isset($seen['database/backup.sql'], $seen['README.txt'])) {
            throw new RuntimeException('The uploaded ZIP is not a supported SPES backup.');
        }

        return ['format' => 'legacy', 'legacy' => true];
    }

    private function isUnsafeLegacyArchivePath(string $path): bool
    {
        if ($path === '' || str_contains($path, "\0") || str_contains($path, '\\') || str_starts_with($path, '/')) {
            return true;
        }
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return true;
            }
        }
        if ($this->isExecutablePath($path)) {
            return true;
        }

        return ! (
            in_array($path, ['README.txt', 'database/backup.sql'], true)
            || str_starts_with($path, 'uploads/private/')
            || str_starts_with($path, 'uploads/public/')
        );
    }

    private function snapshotFromLegacyDump(string $sql): array
    {
        $tables = [];
        foreach ($this->splitSqlStatements($sql) as $statement) {
            $statement = trim($statement);
            if ($statement === '' || str_starts_with($statement, '--')
                || preg_match('/^(?:PRAGMA\s+foreign_keys\s*=\s*(?:OFF|ON)|SET\s+FOREIGN_KEY_CHECKS\s*=\s*[01])$/iD', $statement)) {
                continue;
            }
            if (preg_match('/^(?:DROP\s+TABLE\s+IF\s+EXISTS|CREATE\s+TABLE)\s+/i', $statement)) {
                continue;
            }
            if (! preg_match('/^INSERT\s+INTO\s+`((?:``|[^`])+)`\s*\(/i', $statement, $match, PREG_OFFSET_CAPTURE)) {
                throw new RuntimeException('The legacy database dump contains an unsupported SQL statement.');
            }

            $table = str_replace('``', '`', $match[1][0]);
            $position = $match[0][1] + strlen($match[0][0]);
            $columns = $this->parseLegacyIdentifierList($statement, $position);
            $tail = substr($statement, $position);
            if (! preg_match('/^\s*VALUES\s*/i', $tail, $valuesMatch)) {
                throw new RuntimeException('The legacy database dump contains an unsupported INSERT statement.');
            }
            $position += strlen($valuesMatch[0]);
            $rows = [];
            while (true) {
                $this->skipSqlWhitespace($statement, $position);
                if (($statement[$position] ?? '') !== '(') {
                    break;
                }
                $position++;
                $values = [];
                while (true) {
                    $values[] = $this->parseLegacySqlValue($statement, $position);
                    $this->skipSqlWhitespace($statement, $position);
                    $delimiter = $statement[$position] ?? '';
                    $position++;
                    if ($delimiter === ')') {
                        break;
                    }
                    if ($delimiter !== ',') {
                        throw new RuntimeException('The legacy database dump contains malformed row data.');
                    }
                }
                if (count($values) !== count($columns)) {
                    throw new RuntimeException('The legacy database dump contains mismatched row data.');
                }
                $row = [];
                foreach ($columns as $columnIndex => $column) {
                    $row[$column] = $this->encodeSnapshotValue($values[$columnIndex]);
                }
                $rows[] = $row;
                $this->skipSqlWhitespace($statement, $position);
                if (($statement[$position] ?? '') !== ',') {
                    break;
                }
                $position++;
            }
            $this->skipSqlWhitespace($statement, $position);
            if ($position !== strlen($statement)) {
                throw new RuntimeException('The legacy database dump contains unexpected SQL after its row data.');
            }
            $tables[$table] = array_merge($tables[$table] ?? [], $rows);
        }

        return ['format' => 'spes-db-rows-v1', 'tables' => $tables];
    }

    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $quote = null;
        $length = strlen($sql);
        for ($index = 0; $index < $length; $index++) {
            $character = $sql[$index];
            if ($quote !== null) {
                $buffer .= $character;
                if ($quote === "'" && $character === '\\' && isset($sql[$index + 1])) {
                    $buffer .= $sql[++$index];
                } elseif ($character === $quote) {
                    if (($sql[$index + 1] ?? '') === $quote) {
                        $buffer .= $sql[++$index];
                    } else {
                        $quote = null;
                    }
                }

                continue;
            }
            if ($character === "'" || $character === '"' || $character === '`') {
                $quote = $character;
                $buffer .= $character;
            } elseif ($character === ';') {
                $statements[] = $buffer;
                $buffer = '';
            } else {
                $buffer .= $character;
            }
        }
        if ($quote !== null) {
            throw new RuntimeException('The legacy database dump contains an unterminated quoted value.');
        }
        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }

    private function parseLegacyIdentifierList(string $sql, int &$position): array
    {
        $identifiers = [];
        while (true) {
            $this->skipSqlWhitespace($sql, $position);
            if (($sql[$position] ?? '') !== '`') {
                throw new RuntimeException('The legacy database dump contains an invalid column name.');
            }
            $position++;
            $identifier = '';
            while (isset($sql[$position])) {
                if ($sql[$position] === '`') {
                    if (($sql[$position + 1] ?? '') === '`') {
                        $identifier .= '`';
                        $position += 2;

                        continue;
                    }
                    $position++;
                    break;
                }
                $identifier .= $sql[$position++];
            }
            if ($identifier === '') {
                throw new RuntimeException('The legacy database dump contains an empty column name.');
            }
            $identifiers[] = $identifier;
            $this->skipSqlWhitespace($sql, $position);
            $delimiter = $sql[$position] ?? '';
            $position++;
            if ($delimiter === ')') {
                return $identifiers;
            }
            if ($delimiter !== ',') {
                throw new RuntimeException('The legacy database dump contains a malformed column list.');
            }
        }
    }

    private function parseLegacySqlValue(string $sql, int &$position): mixed
    {
        $this->skipSqlWhitespace($sql, $position);
        if (preg_match('/\GNULL\b/i', $sql, $match, 0, $position)) {
            $position += strlen($match[0]);

            return null;
        }
        if (preg_match('/\G0x([a-f0-9]*)/i', $sql, $match, 0, $position)) {
            $position += strlen($match[0]);

            return hex2bin($match[1]) ?: '';
        }
        if (preg_match('/\GX\'([a-f0-9]*)\'/i', $sql, $match, 0, $position)) {
            $position += strlen($match[0]);

            return hex2bin($match[1]) ?: '';
        }
        if (($sql[$position] ?? '') === "'") {
            $position++;
            $value = '';
            while (isset($sql[$position])) {
                $character = $sql[$position++];
                if ($character === '\\' && isset($sql[$position])) {
                    $escaped = $sql[$position++];
                    $value .= match ($escaped) {
                        '0' => "\0",
                        'n' => "\n",
                        'r' => "\r",
                        'Z' => "\x1a",
                        default => $escaped,
                    };

                    continue;
                }
                if ($character === "'") {
                    if (($sql[$position] ?? '') === "'") {
                        $value .= "'";
                        $position++;

                        continue;
                    }

                    return $value;
                }
                $value .= $character;
            }
            throw new RuntimeException('The legacy database dump contains an unterminated string.');
        }
        if (preg_match('/\G-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?/', $sql, $match, 0, $position)) {
            $position += strlen($match[0]);

            return $match[0];
        }

        throw new RuntimeException('The legacy database dump contains a non-literal SQL value.');
    }

    private function skipSqlWhitespace(string $sql, int &$position): void
    {
        $length = strlen($sql);
        while ($position < $length && ctype_space($sql[$position])) {
            $position++;
        }
    }

    private function isUnsafeArchivePath(string $path): bool
    {
        if ($path === '' || str_contains($path, "\0") || str_contains($path, '\\') || str_starts_with($path, '/')) {
            return true;
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return true;
            }
        }
        if ($this->isExecutablePath($path)) {
            return true;
        }

        return ! (
            in_array($path, ['manifest.json', 'README.txt', 'database/backup.sql', 'database/data.json'], true)
            || str_starts_with($path, 'uploads/private/')
            || str_starts_with($path, 'uploads/public/')
            || str_starts_with($path, 'logs/')
        );
    }

    private function isExecutablePath(string $path): bool
    {
        $name = strtolower(basename($path));

        return in_array(pathinfo($name, PATHINFO_EXTENSION), [
            'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
            'html', 'htm', 'shtml', 'svg', 'js', 'mjs', 'cgi', 'pl', 'asp', 'aspx',
        ], true) || in_array($name, ['.htaccess', '.user.ini'], true);
    }

    private function validateSnapshot(mixed $snapshot): array
    {
        if (! is_array($snapshot) || ($snapshot['format'] ?? null) !== 'spes-db-rows-v1' || ! is_array($snapshot['tables'] ?? null)) {
            throw new RuntimeException('The backup database snapshot has an unsupported structure.');
        }

        $pdo = DB::connection()->getPdo();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $availableTables = array_flip($this->databaseTableNames($pdo, $driver));
        $validated = [];
        foreach ($snapshot['tables'] as $table => $rows) {
            if (! is_string($table) || ! isset($availableTables[$table]) || ! is_array($rows)) {
                throw new RuntimeException("The backup table [{$table}] is not available in this application.");
            }
            $columns = array_flip($this->databaseColumnNames($pdo, $driver, $table));
            $validated[$table] = [];
            foreach ($rows as $row) {
                if (! is_array($row) || array_is_list($row) && $row !== []) {
                    throw new RuntimeException("The backup contains an invalid row in table [{$table}].");
                }
                foreach ($row as $column => $value) {
                    if (! is_string($column) || ! isset($columns[$column])) {
                        throw new RuntimeException("The backup contains an unknown column in table [{$table}].");
                    }
                    $row[$column] = $this->decodeSnapshotValue($value);
                }
                $validated[$table][] = $row;
            }
        }

        return $validated;
    }

    private function databaseColumnNames(PDO $pdo, string $driver, string $table): array
    {
        $identifier = $this->quoteIdentifier($table);
        $statement = $driver === 'mysql'
            ? $pdo->query("SHOW COLUMNS FROM {$identifier}")
            : $pdo->query("PRAGMA table_info({$identifier})");
        $columnIndex = $driver === 'mysql' ? 'Field' : 'name';

        return array_map(
            static fn (array $column): string => (string) $column[$columnIndex],
            $statement->fetchAll(PDO::FETCH_ASSOC),
        );
    }

    private function mergeRows(array $tables, array $manifest): int
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        if ($driver === 'sqlite') {
            $connection->statement('PRAGMA foreign_keys=OFF');
        } elseif ($driver === 'mysql') {
            $connection->statement('SET FOREIGN_KEY_CHECKS=0');
        }

        $importedRows = 0;
        try {
            $connection->transaction(function () use ($tables, &$importedRows, $driver, $connection, $manifest): void {
                foreach ($tables as $table => $rows) {
                    foreach (array_chunk($rows, 200) as $batch) {
                        if ($batch !== []) {
                            $importedRows += DB::table($table)->insertOrIgnore($batch);
                        }
                    }
                }
                if ($driver === 'sqlite') {
                    if ($connection->select('PRAGMA foreign_key_check') !== []) {
                        throw new RuntimeException('The backup contains database records with missing related records.');
                    }
                }
                $this->registerCredential(
                    $manifest,
                    $manifest['archive_type'],
                    $manifest['archive_year'],
                    null,
                );
            });
        } finally {
            if ($driver === 'sqlite') {
                $connection->statement('PRAGMA foreign_keys=ON');
            } elseif ($driver === 'mysql') {
                $connection->statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }

        return $importedRows;
    }

    private function restoreFiles(ZipArchive $zip, array &$createdFiles): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);
            if (! is_string($name)) {
                continue;
            }

            if (str_starts_with($name, 'uploads/private/')) {
                $disk = 'local';
                $relativePath = substr($name, strlen('uploads/private/'));
            } elseif (str_starts_with($name, 'uploads/public/')) {
                $disk = 'public';
                $relativePath = substr($name, strlen('uploads/public/'));
            } elseif (str_starts_with($name, 'logs/')) {
                $disk = null;
                $relativePath = substr($name, strlen('logs/'));
            } else {
                continue;
            }

            if ($disk === null) {
                $targetPath = storage_path('logs'.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
                $exists = is_file($targetPath);
            } else {
                $storage = Storage::disk($disk);
                $targetPath = null;
                $exists = $storage->exists($relativePath);
            }
            if ($exists) {
                continue;
            }

            $stream = $zip->getStream($name);
            if ($stream === false) {
                throw new RuntimeException("Unable to read backup file [{$name}].");
            }
            if ($disk === null) {
                $directory = dirname((string) $targetPath);
                if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
                    fclose($stream);
                    throw new RuntimeException("Unable to create the log directory for [{$name}].");
                }
                $target = fopen((string) $targetPath, 'xb');
                if ($target === false) {
                    fclose($stream);
                    throw new RuntimeException("Unable to restore log file [{$name}].");
                }
                $copied = stream_copy_to_stream($stream, $target);
                fclose($target);
                fclose($stream);
                if ($copied === false) {
                    @unlink((string) $targetPath);
                    throw new RuntimeException("Unable to finish restoring log file [{$name}].");
                }
                $createdFiles[] = ['path' => (string) $targetPath, 'disk' => null];
            } else {
                $written = $storage->writeStream($relativePath, $stream);
                fclose($stream);
                if (! $written) {
                    throw new RuntimeException("Unable to restore stored file [{$name}].");
                }
                $createdFiles[] = ['path' => $relativePath, 'disk' => $disk];
            }
        }

    }

    private function removeRestoredFiles(array $createdFiles): void
    {
        foreach ($createdFiles as $file) {
            if ($file['disk'] === null) {
                if (is_file($file['path'])) {
                    @unlink($file['path']);
                }
            } else {
                Storage::disk($file['disk'])->delete($file['path']);
            }
        }
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    private function quoteValue(PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_string($value) && str_contains($value, "\0")) {
            return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
                ? "X'".bin2hex($value)."'"
                : '0x'.bin2hex($value);
        }

        $quoted = $pdo->quote((string) $value);
        if ($quoted === false) {
            throw new RuntimeException('Unable to safely encode a database value for backup.');
        }

        return $quoted;
    }
}
