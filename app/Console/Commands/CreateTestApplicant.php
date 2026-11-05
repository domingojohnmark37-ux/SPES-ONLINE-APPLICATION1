<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateTestApplicant extends Command
{
    protected $signature = 'spes:create-test-applicant {email}';

    protected $description = 'Create one verified applicant account for portal testing';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Provide a valid email address.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error('An account already exists for that email. No account was changed.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Name for the test applicant'));
        if ($name === '') {
            $this->error('A name is required.');

            return self::FAILURE;
        }

        $password = $this->secret('Choose a password (at least 12 characters, with an uppercase letter, number, and symbol)');
        $confirmation = $this->secret('Confirm the password');
        $validator = Validator::make([
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
                'regex:/\p{Lu}/u',
                'regex:/\d/',
                'regex:/[\p{S}\p{P}]/u',
            ],
        ], [
            'password.min' => 'The password must be at least 12 characters.',
            'password.confirmed' => 'The passwords do not match.',
            'password.regex' => 'The password must include an uppercase letter, a number, and a symbol.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $emailName = Str::before($email, '@');
        $usernameBase = Str::slug($emailName, '_') ?: 'test_applicant';
        $username = $usernameBase;
        $suffix = 1;
        while (User::query()->where('username', $username)->exists()) {
            $username = $usernameBase.'_'.$suffix++;
        }

        $user = User::create([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
        ]);
        $user->forceFill(['role' => 'user'])->save();
        $user->markEmailAsVerified();

        $this->info("Verified applicant account created for {$email}. Sign in with this email and the password you entered.");
        $this->warn('This account bypasses email verification. Use it only for testing and remove it when finished.');

        return self::SUCCESS;
    }
}
