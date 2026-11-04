<?php

return [
    'retention' => [
        'daily' => (int) env('BACKUP_KEEP_DAILY', 7),
        'weekly' => (int) env('BACKUP_KEEP_WEEKLY', 4),
        'monthly' => (int) env('BACKUP_KEEP_MONTHLY', 12),
    ],
    'schedule_time' => env('BACKUP_SCHEDULE_TIME', '02:00'),
    'minimum_free_bytes' => (int) env('BACKUP_MINIMUM_FREE_BYTES', 1_073_741_824),
];
