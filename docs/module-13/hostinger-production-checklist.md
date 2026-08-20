# Hostinger Production Checklist

Do not deploy until the project owner explicitly authorizes deployment.

- Set document root to Laravel `public`.
- Set `APP_ENV=production`.
- Set `APP_DEBUG=false`.
- Set `APP_URL` to the HTTPS production URL.
- Set a generated `APP_KEY`.
- Use Hostinger MySQL/MariaDB credentials in `.env`.
- Set `SESSION_DRIVER=database`.
- Set `SESSION_SECURE_COOKIE=true` after HTTPS is active.
- Set `SESSION_SAME_SITE=lax`.
- Set `QUEUE_CONNECTION=database`.
- Run `composer install --no-dev --optimize-autoloader`.
- Run `npm ci` and `npm run build` before upload/build artifact packaging.
- Run migrations only after a backup and owner authorization.
- Do not seed `IVY_DEV_ADMIN_*` in production unless creating a temporary owner account that is rotated immediately.
- Configure cron:

```cron
* * * * * cd /home/USER/path-to-project && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

- Schedule database backups through Hostinger and keep an off-platform backup copy.
- Protect `.env`, storage, logs and backups from web access.
