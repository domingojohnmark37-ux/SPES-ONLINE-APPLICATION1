<?php

namespace App\Services;

use RuntimeException;

/**
 * Leading/trailing whitespace is removed, repeated whitespace is collapsed, and case is ignored.
 */
class PersonalSecurityAnswer
{
    public static function normalize(string $answer): string
    {
        $answer = preg_replace('/^\s+|\s+$/u', '', $answer);
        if (! is_string($answer)) {
            throw new RuntimeException('The security answer could not be normalized.');
        }

        $answer = preg_replace('/\s+/u', ' ', $answer);
        if (! is_string($answer)) {
            throw new RuntimeException('The security answer could not be normalized.');
        }

        return mb_strtolower($answer, 'UTF-8');
    }
}
