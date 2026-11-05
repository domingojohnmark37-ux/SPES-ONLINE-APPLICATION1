# SPES Application System

A comprehensive Laravel-based document management and approval system for education personnel applications and submissions.

## Features

 **User Management**
- User registration and authentication
- Role-based access control (User/Admin)
- User profile management
- Secure password management

 **Application Submission**
- Dynamic form-based application submissions
- File upload support with secure storage
- Application history and versioning
- Status tracking (pending, approved, denied)

 **Admin Dashboard**
- Centralized application review interface
- Batch application management
- Approval/denial workflows
- Real-time statistics and counts
- User management interface

 **Dashboard & Analytics**
- User dashboard with application status
- Live counts of users and submissions
- Application status overview
- Quick access to important actions

## Technology Stack

- **Backend**: [Laravel 12](https://laravel.com) - PHP web framework
- **Frontend**: [Blade Templates](https://laravel.com/docs/blade) with [Tailwind CSS](https://tailwindcss.com)
- **Database**: MySQL
- **Build Tools**: Vite, PostCSS
- **Testing**: PHPUnit

## Requirements

- PHP 8.2 or higher
- Composer
- MySQL 5.7+
- Node.js 18+ (for frontend assets)
- XAMPP or similar local server (for development)

## Installation

### Docker deployment

The Docker deployment runs Laravel's scheduler automatically alongside the application services.

### 1. Clone & Setup

```bash
# Clone the repository
git clone <repository-url>
cd SPES-ONLINE-APPLICATION1

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

The example environment is configured for local HTTP development and SQLite. If using MySQL, set `DB_CONNECTION=mysql` and configure `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`. Set `ADMIN_PASSWORD` before running the admin seeder.

```bash
# Create or update the database schema
php artisan migrate

# Create the initial admin account; ADMIN_PASSWORD must be set first
php artisan db:seed --class=AdminSeeder

# Install Node dependencies and build frontend assets
npm install
npm run build
```

### Email Notifications

Applicant email notifications support SMTP, Resend, and a free Google Apps Script relay. On Render, SMTP requires a paid instance because the free tier blocks outbound SMTP. Resend uses HTTPS and the Apps Script relay uses Google's HTTPS web app.

```dotenv
APP_URL=https://your-official-portal.example
# Choose smtp or resend
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-authorized-sender@gmail.com
MAIL_PASSWORD=your-gmail-app-password
MAIL_FROM_ADDRESS=your-authorized-sender@gmail.com
MAIL_FROM_NAME="PESO Lal-lo SPES Portal"
# For MAIL_MAILER=resend:
RESEND_API_KEY=your-resend-api-key
# For the free Google Apps Script relay:
MAIL_MAILER=google_apps_script
GOOGLE_APPS_SCRIPT_MAIL_URL=https://script.google.com/macros/s/your-deployment-id/exec
GOOGLE_APPS_SCRIPT_MAIL_TOKEN=your-long-random-shared-token
```

`MAIL_FROM_ADDRESS` must be an address on a domain verified in Resend. Until a domain is verified, Resend can deliver only to the Resend account owner's email address, which is suitable for testing but not for real applicants. For Gmail SMTP, use a Gmail App Password with 2-Step Verification enabled; never use or commit the account password. Set `APP_URL` to the official HTTPS portal URL before deployment.

### Free email with Google Apps Script

The relay sends through the Gmail account that owns the script, over HTTPS, without an SMTP provider or subscription. Gmail accounts have a limit of 100 recipients per day; Google Workspace accounts have a limit of 1,500 recipients per day. Recipients are counted by Google, not by the portal. The script must be redeployed as a new version after any edit. Keep the shared token secret.

1. Sign in at [script.google.com](https://script.google.com) to the Gmail account that will send the emails, then create a new project.
2. Paste the contents of [`docs/google-apps-script-mail-relay.gs`](./docs/google-apps-script-mail-relay.gs) into the project.
3. Open **Project Settings → Script Properties** and add `MAIL_RELAY_TOKEN` with a long random value. Generate one with a password manager or a cryptographically secure generator, for example `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`. Use that same value only in the script property and Render environment.
4. Choose **Deploy → New deployment**, select **Web app**, set **Execute as** to **Me**, and **Who has access** to **Anyone**. Deploy and authorize the requested permissions, then copy the web app URL.
5. In **Render → Environment**, set:
   - `MAIL_MAILER=google_apps_script`
   - `GOOGLE_APPS_SCRIPT_MAIL_URL` to the copied web app URL
   - `GOOGLE_APPS_SCRIPT_MAIL_TOKEN` to the exact same secret as the Script Property
   - `MAIL_FROM_ADDRESS` to the Gmail address that owns the script
   - `MAIL_FROM_NAME` to the desired sender display name
6. Redeploy the Render service.
7. Run `php artisan mail:test someone@example.com` in the Render shell to verify delivery.

Troubleshooting:
- "Connection timed out" or "could not be established" means the SMTP port is blocked (Render free tier).
- "535 Username and Password not accepted" means the Gmail App Password is wrong or 2-Step Verification is off.
- A from-address rejection means `MAIL_FROM_ADDRESS` must match the authenticated sender or a verified domain.

Run `php artisan mail:test my@email.com` in the deployment shell to test the configured mailer. An administrator can also send a real configuration test from **Admin → Settings**; it is delivered to that administrator's registered email address. Run `php artisan schedule:work` or configure Laravel's scheduler to run once per minute so eligible appointment reminders are sent approximately 24 hours before appointments and again on the appointment date.

### Creating a temporary test applicant

If email delivery is unavailable and you need to test the applicant portal on Render, run `php artisan spes:create-test-applicant your-email@example.com` in the Render Shell. The command securely prompts for a name and a password twice, creates a verified applicant without sending email, and refuses to change an existing account. Sign in with the email and password you entered. This account bypasses email verification; use it only for testing and remove it when finished.

### 4. Storage & Assets

```bash
# Create storage symlink for file uploads
php artisan storage:link

# Build frontend assets
npm run build
```

## Development

### Running the Application

```bash
# Start development server
php artisan serve

# In another terminal, compile assets with hot reload
npm run dev
```

Access the application at `http://localhost:8000`

### Build for Production

```bash
npm run build
```

### Running Tests

```bash
php artisan test
```

## Project Structure

```
├── app/
│   ├── Http/
│   │   ├── Controllers/     # Application controllers
│   │   ├── Middleware/      # Route middleware
│   │   └── Requests/        # Form requests & validation
│   ├── Models/              # Eloquent models (User, Application)
│   ├── Providers/           # Service providers
│   └── View/Components/     # Reusable blade components
├── database/
│   ├── migrations/          # Database migrations
│   ├── factories/           # Model factories for testing
│   └── seeders/             # Database seeders
├── resources/
│   ├── views/               # Blade templates
│   ├── css/                 # Tailwind stylesheets
│   └── js/                  # JavaScript files
├── routes/                  # Application routes
├── storage/                 # File uploads & cache
├── tests/                   # PHPUnit tests
└── public/                  # Web root
```

## Application Workflow

### User Flow
1. User registers and logs in
2. Navigate to **Apply** section
3. Fill out application form
4. Submit documents
5. View application status in **My Applications**

### Admin Flow
1. Log in with admin credentials
2. Navigate to **Admin → Applications**
3. Review pending submissions
4. Approve or deny applications
5. View application statistics

## Database Schema

### Users Table
- Standard Laravel user fields
- Added: `username`, `role` (user/admin)

### Applications Table
- user_id (foreign key to users)
- status (pending/approved/denied)
- form_data (JSON - application form details)
- Created/updated timestamps

## API Endpoints

See [routes/web.php](routes/web.php) for complete routing configuration.

Key routes:
- `/` - Welcome page
- `/dashboard` - User dashboard
- `/applications` - Submit new application
- `/applications/my` - View user's applications
- `/admin/applications` - Admin panel (admin only)

## File Storage

Application documents and applicant-submitted additional requirements are stored on Laravel's private `local` disk under `storage/app/private/` and are served only through authenticated, authorized controller routes. Existing public application documents can be moved once with:

```bash
php artisan spes:move-documents-to-private
```

The command is safe to run more than once. Profile photos and other intentionally public files remain on the `public` disk under `storage/app/public/`.

Run `php artisan storage:link` only to expose intentionally public files through `public/storage/`.

For container deployments, migrations run at startup by default. `SESSION_ENCRYPT=true` encrypts session data and will log out existing users once when enabled.

### Trusted Proxies

The app does not trust forwarded headers by default. In production, set `TRUSTED_PROXIES` to a comma-separated list of the actual trusted proxy IPs or CIDRs so client IP detection and IP-based rate limits use the right address. Use `*` only when the app is guaranteed to receive traffic exclusively through a trusted load balancer, such as Render's web-service ingress; do not use it when the application can be reached directly by untrusted clients.

### Render Deployment

This is a PHP/Laravel application. Render does not provide a native PHP runtime, so deploy it as a Docker web service using the repository `Dockerfile` and `render.yaml`. Leave Render's Build Command and Start Command fields empty for a Docker service; the Dockerfile builds the application and its `ENTRYPOINT` starts Nginx, PHP-FPM, and the queue worker.

The container listens on the deployment platform's `PORT` environment variable, defaulting to `3000`. Nginx binds to all container interfaces on that port so the platform health check can reach it. Use `/healthz` for the deployment health check; it returns 200 without accessing the database or session. The public homepage (`/`) queries published news and uses the configured database. The Render blueprint attaches persistent storage for private applicant documents. Configure the service's environment variables in the deployment dashboard.

For EthioDeploy, configure these runtime variables in the service dashboard:

- Required: `APP_KEY` (a generated Laravel key), `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` (the public HTTPS URL), and `PORT=3000` unless the platform supplies `PORT`.
- Required database settings: `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`. The startup script migrates the database before caching config.
- Recommended: `LOG_CHANNEL=stderr`, `CACHE_STORE=database`, `SESSION_DRIVER=database`, `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, and `QUEUE_CONNECTION=database`. The database-backed cache, sessions, and queue require their migrated tables.
- Email is not needed for `/healthz` or `/`. To send email, set `MAIL_MAILER`, `MAIL_FROM_ADDRESS`, and `MAIL_FROM_NAME`, plus credentials for the selected mailer (SMTP variables for SMTP, `RESEND_API_KEY` for Resend, or `GOOGLE_APPS_SCRIPT_MAIL_URL` and `GOOGLE_APPS_SCRIPT_MAIL_TOKEN` for the Apps Script relay).

The Docker entrypoint receives platform environment variables before it runs migrations and `config:cache`. It creates the required storage/cache directories and grants write access before Laravel starts. If `/healthz` returns 204 while `/` returns 500, inspect application logs on stderr; common homepage causes are missing database credentials, an unavailable database, or migrations not applied to the configured database.

Use database-backed drivers in Render and ensure the corresponding `cache`, `jobs`, and `sessions` tables are migrated:

```env
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
```

Ensure proper permissions:
```bash
chmod -R 775 storage/ bootstrap/cache/
```

## Configuration Files

- `config/app.php` - Application settings
- `config/auth.php` - Authentication configuration
- `config/database.php` - Database connection settings
- `config/filesystems.php` - File storage configuration

## Troubleshooting

### Migrations not running?
```bash
php artisan migrate:fresh
php artisan db:seed --class=AdminSeeder
```

### Storage link issues?
```bash
php artisan storage:link
```

### Assets not loading?
```bash
npm run build
php artisan view:clear
```

### Admin user not found?
```bash
php artisan db:seed --class=AdminSeeder
# Default: email=admin@example.com, password=password
```

## Security

- CSRF protection on all forms
- Password hashing with bcrypt
- Secure file upload validation
- Role-based authorization middleware
- SQL injection prevention via Eloquent ORM

## Known Issues & Roadmap

Current version (v1.0) features core functionality. Future enhancements:
- Email notifications for application updates
- Advanced filtering and search
- Bulk export capabilities
- Application templates
- Multi-language support

## Contributing

1. Create a feature branch (`git checkout -b feature/amazing-feature`)
2. Commit changes (`git commit -m 'Add amazing feature'`)
3. Push to branch (`git push origin feature/amazing-feature`)
4. Open a Pull Request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For issues and questions:
- Check the [troubleshooting section](#troubleshooting)
- Review [Laravel documentation](https://laravel.com/docs)
- Open an issue in the repository

---

**Last Updated**: 2026  
**Maintainer**: Development Team
