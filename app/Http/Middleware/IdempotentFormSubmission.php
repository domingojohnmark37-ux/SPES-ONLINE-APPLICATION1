<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class IdempotentFormSubmission
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->input('_idempotency_token');
        if (! is_string($token) || ! preg_match('/\A[a-f0-9]{40}\z/i', $token)) {
            return $next($request);
        }

        $userId = (int) $request->user()->getAuthIdentifier();
        $routeName = (string) $request->route()?->getName();
        $tokenHash = hash('sha256', $token);
        $identity = [
            'user_id' => $userId,
            'route_name' => $routeName,
            'token_hash' => $tokenHash,
        ];

        try {
            return DB::transaction(function () use ($request, $next, $identity): Response {
                DB::table('idempotent_form_submissions')
                    ->where('created_at', '<', now()->subDays(30))
                    ->delete();

                $existing = DB::table('idempotent_form_submissions')
                    ->where($identity)
                    ->lockForUpdate()
                    ->first();
                if ($existing !== null) {
                    return $this->duplicateResponse($existing->redirect_url);
                }

                $submissionId = DB::table('idempotent_form_submissions')->insertGetId([
                    ...$identity,
                    'redirect_url' => $request->headers->get('referer', '/'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $response = $next($request);
                if ($response->getStatusCode() >= 500 || $request->session()->has('errors')) {
                    DB::table('idempotent_form_submissions')->where('id', $submissionId)->delete();

                    return $response;
                }

                DB::table('idempotent_form_submissions')
                    ->where('id', $submissionId)
                    ->update([
                        'redirect_url' => $response->headers->get('Location', '/'),
                        'updated_at' => now(),
                    ]);

                return $response;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $existing = DB::table('idempotent_form_submissions')
                ->where($identity)
                ->first();
            if ($existing === null) {
                throw $exception;
            }

            return $this->duplicateResponse($existing->redirect_url);
        }
    }

    private function duplicateResponse(string $redirectUrl): Response
    {
        return redirect()->to($redirectUrl)->with(
            'info',
            __('This submission was already processed. No duplicate record was created.'),
        );
    }
}
