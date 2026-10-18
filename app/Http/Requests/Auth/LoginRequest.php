<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const DUMMY_PASSWORD_HASH = '$argon2id$v=19$m=65536,t=4,p=1$TWhtOFZER3kuLm1xWnB3OA$DRmUvVd4eC35F7IUixyd2elTreQRrKxWb/1RLwueZJI';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(AuditLogger $auditLogger): User
    {
        $this->ensureIsNotRateLimited();

        $identifier = mb_strtolower(trim($this->string('email')->toString()), 'UTF-8');
        $user = User::query()
            ->where(function ($query) use ($identifier): void {
                $query->whereRaw('LOWER(email) = ?', [$identifier])
                    ->orWhereRaw('LOWER(username) = ?', [$identifier]);
            })
            ->first();

        $password = $this->string('password')->toString();
        if (! $user) {
            Hash::check($password, self::DUMMY_PASSWORD_HASH);
        }

        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->ipThrottleKey());
            $auditLogger->recordFailedLoginAttempt($user);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $throttleKey = $this->throttleKey();
        $ipThrottleKey = $this->ipThrottleKey();
        $ipMaxAttempts = max(1, (int) config('auth.login_ip_max_attempts', 20));

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
        } elseif (RateLimiter::tooManyAttempts($ipThrottleKey, $ipMaxAttempts)) {
            $seconds = RateLimiter::availableIn($ipThrottleKey);
        } else {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    public function ipThrottleKey(): string
    {
        return 'login|'.$this->ip();
    }
}
