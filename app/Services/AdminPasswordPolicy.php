<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Validation\Rules\Password;

class AdminPasswordPolicy
{
    public function rule(): Password
    {
        $minimum = (int) (SystemSetting::query()->value('admin_minimum_password_length') ?: 12);

        return Password::min($minimum)
            ->numbers()
            ->uncompromised()
            ->rules(function ($attribute, $value, $fail): void {
                if (! is_string($value)) {
                    return;
                }

                if (! preg_match('/\p{Lu}/u', $value)) {
                    $fail('The password must contain at least one uppercase letter.');
                }

                if (! preg_match('/[\p{S}\p{P}]/u', $value)) {
                    $fail('The password must contain at least one symbol.');
                }
            });
    }
}
