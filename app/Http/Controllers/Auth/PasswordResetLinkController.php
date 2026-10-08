<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /** Begin the password reset flow without revealing whether the email exists. */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $request->session()->forget([
            'password_reset_flow_token',
            'password_reset_email',
            'password_reset_otp_sent',
        ]);
        $request->session()->regenerate();
        $request->session()->put([
            'password_reset_flow_token' => (string) Str::uuid(),
            'password_reset_email' => strtolower(trim($request->input('email'))),
        ]);

        return redirect()->route('password.reset');
    }
}
