<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
        'email_notifications' => ['boolean', true],
        'system_notifications' => ['boolean', true],
        'application_updates' => ['boolean', true],
        'approval_rejection_notifications' => ['boolean', true],
        'reminder_notifications' => ['boolean', true],
        'theme_preference' => ['string', 'system'],
        'timezone' => ['string', null],
        'date_format' => ['string', 'Y-m-d'],
        'time_format' => ['string', '12h'],
        'number_format' => ['string', 'default'],
        'sidebar_behavior' => ['string', 'auto'],
        'font_size' => ['string', 'medium'],
        'profile_visibility' => ['string', 'private'],
        'personal_information_visibility' => ['string', 'private'],
        'data_sharing_preferences' => ['string', 'limited'],
        'activity_visibility' => ['string', 'private'],
        'high_contrast_mode' => ['boolean', false],
        'larger_text' => ['boolean', false],
        'reduced_motion' => ['boolean', false],
        'keyboard_navigation' => ['boolean', true],
        'screen_reader_support' => ['boolean', false],
        'two_factor_authentication' => ['boolean', false],
        'login_notifications' => ['boolean', true],
        'security_questions' => ['boolean', false],
        'password_requirements' => ['string', 'standard'],
    ];

    public function up(): void
    {
        Schema::table('applicant_settings', function (Blueprint $table): void {
            foreach ($this->columns as $name => [$type, $default]) {
                if (Schema::hasColumn('applicant_settings', $name)) {
                    continue;
                }

                $column = $table->{$type}($name);
                if ($default !== null) {
                    $column->default($default);
                } else {
                    $column->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        $columns = array_keys($this->columns);
        $existingColumns = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn('applicant_settings', $column),
        ));

        if ($existingColumns !== []) {
            Schema::table('applicant_settings', function (Blueprint $table) use ($existingColumns): void {
                $table->dropColumn($existingColumns);
            });
        }
    }
};
