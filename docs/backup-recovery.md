# Backup and recovery operations

## What this implementation does

- Creates full database-and-upload ZIP archives. It does not create incremental backups or use MySQL binary logs.
- Schedules a full automatic backup from the saved administrator settings. Defaults are enabled, daily at 02:00 Asia/Manila, with seven days of retention; weekly backups run on Monday at the selected time.
- Runs manual and scheduled backup jobs synchronously by default, so a backup does not remain queued just because no worker is running. Larger installations can set `BACKUP_EXECUTION_MODE=queue` and use the existing managed Laravel queue worker.
- Keeps annual full archives outside automatic retention. They are never removed by the cleanup command.
- Applies the saved retention period to verified daily/weekly archives and completed administrator backup operations. Always keeps the newest verified recovery point, archives in use, annual archives, and separate restore safety archives.
- Protects a backup referenced by a queued or active operation. Cleanup uses a shared cache lock, records every run in `backup_cleanup_runs`, and only removes inventoried, verified archives.
- Records operation status, duration, archive size, verification time, failures, and audit entries. Interrupted work is marked failed and is not blindly replayed.
- Shows backup storage usage and volume free space only when the configured local filesystem can report it.
- The administrator settings page shows automatic-backup status, backup history, and available verified downloads. The administrator dashboard also shows backup status and the next saved run.
- Before a queued or console merge restore changes data, the archive is fully validated and a fresh verified safety archive is saved under private `backups/recovery-safety/`. Safety archives are kept separate from normal retention cleanup.

The current archive implementation still writes both a SQL dump and a JSON row snapshot. The JSON snapshot has a 256 MB limit and is decoded into PHP memory by the existing merge restore. Synchronous execution is intended for small installations; use managed queue processing for larger databases or archives. The administrator **Restore from Backup** workflow remains a merge into the active database: it adds missing records and files, does not overwrite existing records, and does not execute the included SQL dump. Its safety archive supports operator recovery but does not make the merge a full-system replacement restore.

Applicant documents are stored on the private `local` disk under `storage/app/private` (including `applications/...`). Profile pictures use the `public` disk. The ZIP includes both disks and the database, so restore the database and files from the same archive. Applicant documents and archives contain sensitive personal information; keep both disks outside the web root and restrict filesystem access.

## Dependencies and configuration

Required PHP extensions: `pdo_sqlite` or `pdo_mysql` matching `DB_CONNECTION`, `zip`, `zlib`, and `fileinfo`. PHP CLI and the web process must use the same PHP configuration. The active runtime inspected in this checkout is Laravel 12.69.1 / PHP 8.2.12 with SQLite; local MySQL tools are MariaDB 10.4.32 but are not the configured application connection. No claim is made about a separate production server's MySQL version or its disk/CPU/network metrics.

The backup controls are saved in Admin Settings → Backup. Deployment-only configuration defaults:

```dotenv
BACKUP_EXECUTION_MODE=sync
BACKUP_TIMEZONE=Asia/Manila
BACKUP_MINIMUM_FREE_BYTES=1073741824
```

`BACKUP_EXECUTION_MODE=sync` does not change `QUEUE_CONNECTION`; it only controls these backup operations. Synchronous execution avoids a worker dependency for small installations, but a large archive may exceed web-server request time limits. Set it to `queue` for larger installations and have the hosting environment manage a worker continuously.

The local backup disk must be private, writable by the web process, and have enough free space for a temporary archive plus the finished archive. Queue mode additionally requires write access for the managed worker. The code stops a backup when the disk reports less than the configured free-space threshold. Remote disks that cannot report free space show “unavailable”; they are not represented as monitored.

## Deployment

From the application release directory, once per deployment:

```powershell
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan schedule:list
```

For `BACKUP_EXECUTION_MODE=queue`, run a queue worker continuously, supervised and restarted after each deployment:

```powershell
php artisan queue:work database --queue=default --sleep=3 --tries=1 --timeout=900
```

Keep the database queue connection's `retry_after` above the worker timeout (the current default is 1020 seconds). Do not run the worker with `--timeout=0`. Use Supervisor/systemd on Linux or an approved Windows service wrapper such as NSSM on Windows; configure automatic restart and monitor worker/failed-job logs. The queue and scheduler must use the same application release, environment, database, cache, and private storage.

Configure Laravel's scheduler to run every minute, once during deployment. On Windows Task Scheduler, create a task whose program is the deployment's PHP CLI executable, whose arguments are `artisan schedule:run`, and whose **Start in** directory is the project directory. Configure it to run whether or not an administrator is logged in and to restart on failure. On Linux, use the standard single cron entry `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`. Shared hosting must provide an equivalent recurring task; if it cannot run at least once per minute, automatic backup timing cannot be guaranteed.

The scheduler runs the saved daily/weekly schedule check, daily retention cleanup, interrupted-operation checks, and the existing annual archive on December 31 at 11:59 PM Asia/Manila. In default synchronous backup mode, the scheduled command itself creates and verifies the archive; no backup queue worker is needed. If queue mode is enabled, a managed worker is also required. Laravel cannot start the operating-system scheduler or keep a worker alive by itself.

The deployment operator can use `php artisan schedule:list` to inspect configured schedules; it does not prove that Windows Task Scheduler/production scheduler or the storage volume is operational. Monitor the displayed last-success time and failed operations. Administrators do not need to run backup, queue-worker, or scheduler commands.

## Verification and safe testing

The focused feature tests use PHPUnit's in-memory SQLite database and isolated fake storage:

```powershell
php artisan test --filter=AdminSettingsFeaturesTest
```

Do not use a production database or production storage for destructive restore tests. The current test suite verifies archive integrity and merge behavior; it does **not** validate large-database throughput, MySQL import/cutover, real disk exhaustion, process termination during I/O, or staged restore activation. Those scenarios require a disposable database and storage volume in a deployment-like environment before enabling a full replacement restore.

## Full disaster-recovery limitation and safe operator procedure

The portal does **not** implement a full replacement restore or database cutover. It cannot safely replace modified records or guarantee an atomic rollback on both SQLite and MySQL. The included SQL dump is for controlled operator recovery and is not executed by the portal. Do not treat **Restore from Backup** or `backup:restore-annual` as full disaster recovery.

For a full recovery, involve the database/system operator:

1. Put the application into maintenance mode and stop the scheduler and queue worker so writes and backup jobs are fenced.
2. Preserve the current database, private/public storage, and the separate `backups/recovery-safety/` archive. Do not overwrite or delete the only current copy.
3. Validate the selected archive and restore its database snapshot and files into an isolated staging database/storage using the matching database driver and a compatible application release. Verify required tables, relationships, files, and application behavior there.
4. For MySQL, use a separately provisioned staging database and a rehearsed, versioned connection cutover with a tested rollback path. Never import the dump destructively into the live database. For SQLite, stop all application connections and use a verified staged database file with a copy-based rollback plan.
5. Cut over only after validation and an independently verified live-system safety copy; then verify the application before restarting workers and the scheduler. If validation or cutover fails, return to the preserved live copy.

This full replacement/cutover procedure is an operational recovery path, not a feature implemented by this portal. Do not blindly retry a failed merge; inspect its operation record, current database, files, and logs first.
