<?php

namespace App\Support;

class DeviceIdentifier
{
    public static function describe(?string $userAgent): string
    {
        $agent = (string) $userAgent;

        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Microsoft Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'Firefox/') => 'Mozilla Firefox',
            str_contains($agent, 'Chrome/') => 'Google Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Unknown browser',
        };

        $platform = match (true) {
            str_contains($agent, 'iPhone') => 'iPhone',
            str_contains($agent, 'iPad') => 'iPad',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Windows NT') => 'Windows',
            str_contains($agent, 'Mac OS X') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'Unknown device',
        };

        $deviceType = preg_match('/Mobile|iPhone|Android/i', $agent) === 1
            ? 'Mobile'
            : (in_array($platform, ['Windows', 'macOS', 'Linux'], true) ? 'Desktop' : null);

        return implode(' on ', array_filter([
            $browser,
            $deviceType ? "{$platform} ({$deviceType})" : $platform,
        ]));
    }
}
