<?php

namespace App\Models;

final class AuditAction
{
    public const APPLICANT_VIEWED = 'Applicant Viewed';

    public const ACCOUNT_CREATED = 'Account Created';

    public const APPLICATION_SUBMITTED = 'Application Submitted';

    public const APPLICATION_UPDATED = 'Application Updated';

    public const APPLICATION_APPROVED = 'Application Approved';

    public const APPLICATION_REJECTED = 'Application Rejected';

    public const APPLICATION_STATUS_CHANGED = 'Application Status Changed';

    public const REMARKS_ADDED = 'Remarks Added';

    public const INFORMATION_UPDATED = 'Information Updated';

    public const DOCUMENT_SUBMITTED = 'Document Submitted';

    public const LOGIN_SUCCESS = 'Login Success';

    public const LOGIN_FAILED = 'Login Failed';

    public const LOGOUT = 'Logout';

    public const PASSWORD_CHANGED = 'Password Changed';

    public const SECURITY_POLICY_UPDATED = 'Security Policy Updated';

    public const ADMIN_PREFERENCES_UPDATED = 'Admin Preferences Updated';

    public const APPLICATION_PERIOD_UPDATED = 'Application Period Updated';

    public const DATABASE_BACKUP_GENERATED = 'Database Backup Generated';

    public const DATABASE_BACKUP_RESTORED = 'Database Backup Restored';

    public const DATABASE_BACKUP_DOWNLOADED = 'Database Backup Downloaded';

    public const APPLICANT_DELETED = 'Applicant Deleted';

    public const APPLICANT_REPORT_GENERATED = 'Applicant Report Generated';

    public const ALL = [
        self::APPLICANT_VIEWED,
        self::ACCOUNT_CREATED,
        self::APPLICATION_SUBMITTED,
        self::APPLICATION_UPDATED,
        self::APPLICATION_APPROVED,
        self::APPLICATION_REJECTED,
        self::APPLICATION_STATUS_CHANGED,
        self::REMARKS_ADDED,
        self::INFORMATION_UPDATED,
        self::DOCUMENT_SUBMITTED,
        self::LOGIN_SUCCESS,
        self::LOGIN_FAILED,
        self::LOGOUT,
        self::PASSWORD_CHANGED,
        self::SECURITY_POLICY_UPDATED,
        self::ADMIN_PREFERENCES_UPDATED,
        self::APPLICATION_PERIOD_UPDATED,
        self::DATABASE_BACKUP_GENERATED,
        self::DATABASE_BACKUP_RESTORED,
        self::DATABASE_BACKUP_DOWNLOADED,
        self::APPLICANT_DELETED,
        self::APPLICANT_REPORT_GENERATED,
    ];

    private function __construct() {}
}
