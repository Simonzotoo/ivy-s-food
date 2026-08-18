# Hostinger Deployment Notes

1. Upload the application outside the public web root.
2. Point the public document root to the Laravel `public` directory only.
3. Create a Hostinger MySQL/MariaDB database and set `.env` database variables.
4. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` to the live HTTPS URL and generate `APP_KEY`.
5. Run `composer install --no-dev --optimize-autoloader`.
6. Run `npm ci` and `npm run build` locally or in a deployment environment, then upload `public/build`.
7. Run `php artisan migrate --force --seed`.
8. Configure cron for Laravel Scheduler:

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

9. Do not rely on a permanently running queue worker on shared hosting.
10. Protect `.env`, storage files, backups and logs from public access.
