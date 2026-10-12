# Troubleshooting slow requests

1. In browser DevTools, open **Network** and inspect the request that remains pending. If it finishes with a 502 or 504, note the response time and route; if it finishes successfully, check whether the page simply missed the response.
2. Check the Render service logs around that timestamp for nginx/php-fpm 502 or 504 errors, worker restarts, and deployment events.
3. Check the `jobs` and `failed_jobs` tables for queued work that is waiting or repeatedly failing. Confirm the queue worker is running and is connected to the same database.
4. Check `storage/logs/laravel.log` for the `Slow HTTP request.` entries and SMTP/provider errors. `MAIL_TIMEOUT` defaults to 10 seconds; verify the mail provider is reachable and its credentials are current.
5. For a settings change that might have timed out, refresh the page and verify the saved values before submitting again.
