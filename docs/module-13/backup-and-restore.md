# Backup And Restore

Minimum production backup procedure:

- Daily MySQL/MariaDB dump.
- Daily copy of `storage/app` if production file uploads are later enabled.
- Keep at least 7 daily and 4 weekly backup points.
- Store one encrypted backup copy outside Hostinger.

Restore drill:

1. Put the application in maintenance mode.
2. Restore the database dump to a staging database first.
3. Verify migrations, login, users, stock balances, sales, payments, credit invoices and reports.
4. Restore files if applicable.
5. Point production `.env` to the restored database only after verification.
6. Bring the application out of maintenance mode.

Never test restore directly on live production data without owner approval.
