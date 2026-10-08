<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\AdminPasswordPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request, AuditLogger $auditLogger, AdminPasswordPolicy $adminPasswordPolicy): RedirectResponse
    {
        $passwordRule = $request->user()?->role === 'admin'
            ? $adminPasswordPolicy->rule()
            : Password::defaults();

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', $passwordRule, 'confirmed'],
        ]);

        $user = $request->user();
        DB::transaction(function () use ($user, $validated, $auditLogger): void {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
            $auditLogger->recordPasswordChanged($user);
        });

        return back()->with('status', 'password-updated');
    }
}
