<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationVerificationCode;
use App\Models\PendingRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $email = mb_strtolower(trim((string) $request->input('email')));
        $request->merge(['email' => $email]);

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            'terms_accepted' => ['accepted'],
        ], [
            'password.min' => 'The password must be at least 12 characters.',
            'password.numbers' => 'The password must contain at least one number.',
            'password.uppercase' => 'The password must contain at least one uppercase letter.',
            'password.symbols' => 'The password must contain at least one symbol.',
            'password.uncompromised' => 'This password has appeared in a data breach. Please choose a different password.',
            'terms_accepted.accepted' => 'Please agree to the Terms and Conditions before continuing.',
        ]);

        PendingRegistration::where('expires_at', '<=', now())->delete();

        $pending = PendingRegistration::where('email', $validated['email'])
            ->where('expires_at', '>', now())
            ->first();

        if (! $pending) {
            if (! $this->verificationMailerIsSafe()) {
                return back()
                    ->withInput($request->except(['password', 'password_confirmation']))
                    ->withErrors(['email' => 'Email verification is unavailable. Please try again later.']);
            }

            $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $pending = PendingRegistration::create([
                'token' => (string) Str::uuid(),
                'email' => $validated['email'],
                'password_hash' => Hash::make($validated['password']),
                'pin_hash' => Hash::make($pin),
                'pin_expires_at' => now()->addMinutes(10),
                'expires_at' => now()->addDay(),
                'last_sent_at' => now(),
            ]);

            Mail::to($pending->email)->send(new RegistrationVerificationCode($pin));
        }

        $request->session()->put([
            'pending_registration_token' => $pending->token,
            'pending_registration_email' => $pending->email,
        ]);

        return redirect()->route('registration.verify');
    }

    private function verificationMailerIsSafe(): bool
    {
        if (app()->environment('testing')) {
            return true;
        }

        $mailer = (string) config('mail.default');
        $fallbackMailers = config("mail.mailers.{$mailer}.mailers", []);

        return ! in_array($mailer, ['log', 'array'], true)
            && ! in_array('log', $fallbackMailers, true);
    }
}
