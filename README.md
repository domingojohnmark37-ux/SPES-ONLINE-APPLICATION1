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

### 1. Clone & Setup

```bash
# Clone the repository
git clone <repository-url>
cd spes_duplicate\ -updates

# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

Configure your `.env` file with database credentials:
```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=spes_db
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Database Setup

```bash
# Run migrations
php artisan migrate

# Seed initial data (admin user)
php artisan db:seed --class=AdminSeeder

# Or seed all data
php artisan db:seed
```

### Email Notifications

Applicant email notifications use Laravel's configured SMTP mailer. For Gmail, configure the sender account in `.env`:

```dotenv
APP_URL=https://your-official-portal.example
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-authorized-sender@gmail.com
MAIL_PASSWORD=your-gmail-app-password
MAIL_FROM_ADDRESS=your-authorized-sender@gmail.com
MAIL_FROM_NAME="PESO Lal-lo SPES Portal"
```

Use a Gmail App Password for an account with 2-Step Verification enabled; never use or commit the account password. Set `APP_URL` to the official HTTPS portal URL before deployment. An administrator can send a real configuration test from **Admin → Settings**; it is delivered to that administrator's registered email address. Run `php artisan schedule:work` or configure Laravel's scheduler to run once per minute so eligible appointment reminders are sent approximately 24 hours before appointments and again on the appointment date.

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

Applicant documents are stored on the `public` filesystem disk under:
- `storage/app/public/applications/` - Application documents
- `storage/app/public/profile-photos/` - Profile photos

Run `php artisan storage:link` so these files are available through `public/storage/`.

### Render Deployment

This is a PHP/Laravel application. Render does not provide a native PHP runtime, so deploy it as a Docker web service using the repository `Dockerfile` and `render.yaml`. Leave Render's Build Command and Start Command fields empty for a Docker service; the Dockerfile builds the application and its `ENTRYPOINT` starts Nginx, PHP-FPM, and the queue worker.

The container listens on Render's `PORT` environment variable, defaulting to `10000` locally. Applicant documents and profile photos are stored on the local filesystem, so attach persistent storage or configure an S3-compatible disk before accepting production uploads.

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
