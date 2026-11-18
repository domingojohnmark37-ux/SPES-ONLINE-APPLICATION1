<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class SystemSetting extends Model
{
    protected $fillable = [
        'application_start_date',
        'application_end_date',
        'approved_applicant_limit',
        'admin_minimum_password_length',
        'admin_session_timeout_minutes',
        'backup_enabled',
        'backup_frequency',
        'backup_time',
        'backup_retention_days',
    ];

    protected $casts = [
        'application_start_date' => 'datetime',
        'application_end_date' => 'datetime',
        'approved_applicant_limit' => 'integer',
        'admin_minimum_password_length' => 'integer',
        'admin_session_timeout_minutes' => 'integer',
        'backup_enabled' => 'boolean',
        'backup_retention_days' => 'integer',
    ];

    /**
     * Get or create the current system settings (single row)
     */
    public static function current()
    {
        $settings = self::firstOrCreate([]);
        static $hasBackupSettingsColumns;
        $hasBackupSettingsColumns ??= Schema::hasColumns('system_settings', [
            'backup_enabled',
            'backup_frequency',
            'backup_time',
            'backup_retention_days',
        ]);
        $columns = [
            'backup_enabled' => true,
            'backup_frequency' => 'daily',
            'backup_time' => '02:00',
            'backup_retention_days' => 7,
        ];
        if ($hasBackupSettingsColumns) {
            $missingDefaults = [];
            foreach ($columns as $column => $default) {
                if ($settings->{$column} === null) {
                    $missingDefaults[$column] = $default;
                }
            }
            if ($missingDefaults !== []) {
                $settings->forceFill($missingDefaults)->save();
            }
        }

        return $settings;
    }

    /**
     * Check if applications are currently open
     */
    public function isApplicationOpen()
    {
        if (app(\App\Services\ApplicationApprovalCapacity::class)->isFull($this)) {
            return false;
        }

        $timezone = config('app.timezone', 'UTC');
        $now = now()->setTimezone($timezone);

        if ($this->application_start_date === null && $this->application_end_date === null) {
            return true; // Default to open when no application window is configured.
        }

        if ($this->application_start_date === null || $this->application_end_date === null) {
            return false; // Partial configuration means the window is not valid yet.
        }

        $start = $this->application_start_date->setTimezone($timezone);
        $end = $this->application_end_date->setTimezone($timezone);

        return $now->greaterThanOrEqualTo($start) && $now->lessThanOrEqualTo($end);
    }
}
