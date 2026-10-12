# Personalized Security Verification

The optional personal question is a knowledge-based challenge performed after the
account password. It is not an independent second factor: someone who knows or
guesses both the password and answer can pass both steps. Passkeys or authenticator
apps provide a stronger independent factor and can be added separately.

## Answers and recovery

Answers are normalized before storage and verification: leading/trailing whitespace
is removed, repeated Unicode whitespace is collapsed to one space, and case is
ignored.
The normalized answer is SHA-256 pre-hashed to avoid bcrypt's input-length
truncation, then stored using Laravel's salted password hasher. The original
answer is never stored or included in audit records.

After password verification, the question challenge is held in the session for
fifteen minutes without authenticating the user. Five incorrect answers per account
or IP trigger a temporary lockout. Recovery sends a short-lived code to the
registered email address after the password has been verified. Successful
recovery clears the prior question and answer and requires a new question setup
before any protected page is available.

If the recovery email is inaccessible, the account holder must contact an
authorized system administrator through the established identity-verification
process. Administrators should not ask users to disclose a security answer.

Apply the database migration with `php artisan migrate --force` when deploying.
