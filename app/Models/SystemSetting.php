<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'application_start_date',
        'application_end_date',
    ];

    protected $casts = [
        'application_start_date' => 'datetime',
        'application_end_date' => 'datetime',
    ];

    /**
     * Get or create the current system settings (single row)
     */
    public static function current()
    {
        return self::firstOrCreate([]);
    }

    /**
     * Check if applications are currently open
     */
    public function isApplicationOpen()
    {
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
