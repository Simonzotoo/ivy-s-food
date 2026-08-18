# Ivy's Food Management System

Laravel 13 modular monolith for Ivy's Food, a Ghana-based yoghurt and fruit juice manufacturer.

## Module Status

Module 1: Application Foundation, Authentication, Users, Roles, Permissions, Company Settings and Audit Foundation is `Tested`. Approval is reserved for the project owner.

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

For local SQLite development:

```env
DB_CONNECTION=sqlite
```

Create the local SQLite file if needed:

```bash
touch database/database.sqlite
php artisan migrate:fresh --seed
npm run build
php artisan serve --host=127.0.0.1 --port=8026
```

Optional local development owner seeding uses `.env` values:

```env
IVY_DEV_ADMIN_NAME="Development Owner"
IVY_DEV_ADMIN_EMAIL=owner@ivys-food.test
IVY_DEV_ADMIN_PASSWORD=change-this-local-password
```

Never commit `.env`, real credentials, production passwords, database dumps or private keys.

## Production Environment

Use MySQL/MariaDB on Hostinger:

```env
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Africa/Accra
APP_CURRENCY=GHS
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
QUEUE_CONNECTION=database
SESSION_DRIVER=database
MAIL_MAILER=smtp
```

Set Hostinger's public document root to Laravel's `public` directory only. Do not expose the project root.

## Build, Test And Quality

```bash
php artisan test
./vendor/bin/pint --test
composer validate --strict
npm run build
```

Migration rollback/reapply check:

```bash
php artisan migrate:rollback --step=6 --env=testing
php artisan migrate --seed --env=testing
```

## Module 1 Features

- Secure login, logout, forgot-password and password reset.
- Active/suspended account enforcement, login throttling, session regeneration and CSRF protection.
- Staff-only account creation; no public registration.
- Configurable roles and granular permissions.
- Company settings seeded for Ivy's Food, GHS, Africa/Accra and 30-day default credit period.
- Reusable audit log service with sensitive-value redaction.
- Responsive Blade/Tailwind administrative interface using the official Ivy's Food logo.

## Source Assets

Original stakeholder files are preserved in `docs/source/`. The application uses a copied logo at `public/images/ivys-logo.png`.
