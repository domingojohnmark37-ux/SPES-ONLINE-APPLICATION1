<?php

namespace App\Support;

use App\Models\AdminPreference;
use Carbon\Carbon;
use DateTimeInterface;

class AdminDateFormatter
{
    public function format(DateTimeInterface|string|null $value, bool $includeTime = false): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $user = auth()->user();
        $preference = $user?->role === 'admin'
            ? ($user->relationLoaded('adminPreference')
                ? $user->getRelation('adminPreference')
                : AdminPreference::firstOrNew(['user_id' => $user->id]))
            : null;
        $timezone = $preference?->timezone ?: config('app.timezone', 'Asia/Manila');
        $dateFormat = $preference?->date_format ?: 'M j, Y';
        $date = $value instanceof DateTimeInterface
            ? Carbon::instance($value)
            : Carbon::parse($value);

        if ($includeTime) {
            $date->setTimezone($timezone);
        }

        return $date->format($dateFormat.($includeTime ? ' · g:i A' : ''));
    }
}
