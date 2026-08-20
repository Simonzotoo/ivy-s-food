# Performance Review

- The system remains a Laravel modular monolith suitable for Hostinger Business shared hosting.
- Queues use the database driver and do not require a permanently running worker.
- Scheduled tasks run through Laravel Scheduler and cron.
- Large reports use query filters and exports are streamed for CSV.
- PDF exports use Dompdf and should be limited to reasonable date ranges on shared hosting.
- Indexes exist for key dates, statuses, foreign keys and lookup fields across operational tables.
- Future VPS migration can introduce persistent workers, Redis cache and more aggressive report caching.

Operational guidance:

- Keep report date ranges narrow on shared hosting.
- Run `php artisan optimize`, `config:cache`, `route:cache` and `view:cache` during production deployment.
- Monitor database size for audit logs, imports and stock movements.
