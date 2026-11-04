# Backup and recovery operations

## What this implementation does

- Creates full database-and-upload ZIP archives. It does not create incremental backups or use MySQL binary logs.
- Schedules a full daily backup at `BACKUP_SCHEDULE_TIME` (default `02:00`, application timezone) on the database queue.
- Keeps annual full archives outside automatic retention. They are never removed by the cleanup command.
- Applies retention to verified daily archives and completed administrator backup/upload operations: keep the most recent configured daily window, the newest backup in each of the configured weekly buckets, and the newest backup in each configured monthly bucket. Always keep the newest verified recovery point.
- Protects a backup referenced by a queued or active operation. Cleanup uses a shared cache lock, records every run in `backup_cleanup_runs`, and only removes inventoried, verified archives.
- Records operation status, duration, archive size, verification time, failures, and audit entries. Interrupted work is marked failed and is not blindly replayed.
- Shows backup storage usage and volume free space only when the configured local filesystem can report it.

The current archive implementation still writes both a SQL dump and a JSON row snapshot. The JSON snapshot has a 256 MB limit and is decoded into PHP memory by the existing merge restore. Daily backups are asynchronous, but this format is not yet suitable for arbitrarily large databases. The existing administrator restore remains a **merge** into the active database; it is not the staged database cutover selected for a disaster-recovery replacement. Do not use it as a replacement restore.

Applicant documents are stored on the private `local` disk under `storage/app/private` (including `applications/...`). Profile pictures use the `public` disk. The ZIP includes both disks and the database, so restore the database and files from the same archive. Applicant documents and archives contain sensitive personal information; keep both disks outside the web root and restrict filesystem access.

## Dependencies and configuration

Required PHP extensions: `pdo_sqlite` or `pdo_mysql` matching `DB_CONNECTION`, `zip`, `zlib`, and `fileinfo`. PHP CLI and the web process must use the same PHP configuration. The active runtime inspected in this checkout is Laravel 12.69.1 / PHP 8.2.12 with SQLite; local MySQL tools are MariaDB 10.4.32 but are not the configured application connection. No claim is made about a separate production server's MySQL version or its disk/CPU/network metrics.

Set these in the deployment environment (values shown are defaults):

```dotenv
QUEUE_CONNECTION=database
BACKUP_KEEP_DAILY=7
BACKUP_KEEP_WEEKLY=4
BACKUP_KEEP_MONTHLY=12
BACKUP_SCHEDULE_TIME=02:00
BACKUP_MINIMUM_FREE_BYTES=1073741824
```

The local backup disk must be private, writable by both the web process and queue worker, and have enough free space for a temporary archive plus the finished archive. The code stops a backup when the disk reports less than the configured free-space threshold. Remote disks that cannot report free space show “unavailable”; they are not represented as monitored.

## Deployment

From the application release directory:

```powershell
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan schedule:list
```

Run one queue worker continuously, supervised and restarted after each deployment:

```powershell
php artisan queue:work database --queue=default --sleep=3 --tries=1 --timeout=900
```

Keep the database queue connection's `retry_after` above the worker timeout (the current default is 1020 seconds). Do not run the worker with `--timeout=0`. Use Supervisor/systemd on Linux or an approved Windows service wrapper such as NSSM on Windows; configure automatic restart and monitor worker/failed-job logs. The queue and scheduler must use the same application release, environment, database, cache, and private storage.

Run Laravel's scheduler every minute. On Windows Task Scheduler, create a task that invokes `php artisan schedule:run` with the project directory as its working directory. The application schedules daily backup, daily cleanup, interrupted-operation checks, and the existing annual archive. Without a functioning scheduler and queue worker, scheduled backups do not run.

Manual commands:

```powershell
php artisan backup:daily
php artisan backup:cleanup
php artisan backup:check-interrupted
php artisan backup:annual
```

## Verification and safe testing

The focused feature tests use PHPUnit's in-memory SQLite database and isolated fake storage:

```powershell
php artisan test --filter=AdminSettingsFeaturesTest
```

Do not use a production database or production storage for destructive restore tests. The current test suite verifies archive integrity and merge behavior; it does **not** validate large-database throughput, MySQL import/cutover, real disk exhaustion, process termination during I/O, or staged restore activation. Those scenarios require a disposable database and storage volume in a deployment-like environment before enabling a full replacement restore.

## Recovery limitation and operator procedure

The administrator restore currently validates the archive and adds missing rows/files without overwriting existing records. It can fail after some MySQL writes because a multi-statement MySQL import is not transactionally reversible. Do not blindly resubmit after a failure; inspect the operation, database, and logs first. A full replacement restore into a verified staging database, its safety-backup lifecycle, maintenance/write fencing, and an explicit cutover/rollback command remain deployment work and are not provided by this change.
