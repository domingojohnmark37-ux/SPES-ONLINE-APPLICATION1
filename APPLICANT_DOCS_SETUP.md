# Applicant Document Storage

Applicant documents are private files. Initial application documents and additional-requirement submissions are written to Laravel's `local` disk, rooted at `storage/app/private`. Database rows retain the relative path and original filename.

## Private access

- Do not create a public storage symlink for applicant documents or link to `/storage/...`.
- Applicants can view only documents belonging to their own application. Administrators can view application documents through authenticated admin pages.
- Document preview routes stream files from the private disk and verify the user's authorization before returning a file.
- A database path does not prove that the file is present. The application displays missing-file warnings when a stored path no longer resolves.

## Durable deployment storage

The local disk must persist across application restarts and deployments. The included `render.yaml` mounts a persistent disk at `/var/www/html/storage/app/private`, matching Laravel's `local` disk root in this deployment.

For other hosting environments, mount durable storage at the configured private-disk root (or migrate the document code and configuration together to a private object-storage disk). A container's temporary filesystem is not suitable: files stored there can disappear when the instance is replaced. Keep `FILESYSTEM_DISK=public` from making applicant documents public; application document code explicitly uses the private `local` disk.

## Additional requirement submissions

Each submitted file has its own row in `application_additional_requirements`. The `(application_id, additional_requirement_id, file_number)` unique index allows multiple independent files for one requirement without duplicate slots. Confirm the multiple-submission migration is applied:

```powershell
php artisan migrate:status
```

The migration is `2026_10_09_000002_allow_multiple_additional_requirement_submissions`. Do not reset the database to apply it.

## Verification

```powershell
php artisan test tests\Feature\ApplicationTest.php
php artisan test tests\Feature\AdditionalRequirementsTest.php
php artisan test tests\Feature\ApplicationApprovalCapacityTest.php
```

Feature tests cover authenticated private viewing, stored paths and original filenames, multiple requirement files, refresh persistence, and cleanup when a database transaction fails.
