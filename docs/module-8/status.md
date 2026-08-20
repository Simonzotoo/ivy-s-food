# Module 8 Status

Status: Tested

Implementation commit: Pending

Scope:

- Default 30-day credit terms.
- Credit invoice statuses.
- Payment allocations.
- Multiple and partial repayments.
- MoMo network and transaction reference.
- Customer account statements.
- Payment receipts.
- Credit-aging report.
- Overdue-account controls.
- Dashboard reminders.
- Scheduled reminders.
- Reminder templates and history.
- Provider-neutral SMS/WhatsApp delivery adapters.

Business rules:

- No real SMS or WhatsApp message is sent in development.
- Payments are allocated to credit invoices through auditable records.
- Customer outstanding balances are reduced only by recorded allocations.
- Reminder sending uses a local/test channel until a provider is approved.

Database changes:

- `customer_payments`
- `payment_allocations`
- `reminder_templates`
- `credit_reminders`

Routes:

- `GET /credit/invoices`
- `GET /credit/invoices/{invoice}`
- `POST /credit/invoices/{invoice}/reminders`
- `GET /credit/payments`
- `POST /credit/payments`
- `GET /credit/payments/{payment}/receipt.pdf`
- `GET /credit/customers/{customer}/statement`
- `GET /credit/aging`
- `GET /credit/reminders`
- `POST /credit/reminders/templates`

Permissions:

- `credit.view`
- `credit.payments`
- `credit.reminders`
- `credit.reports`

Verification:

- Focused Module 8 tests passed: 5 tests, 22 assertions.
- Full regression suite passed: 62 tests, 276 assertions.
- Migration fresh/seeding passed.
- Migration rollback and reapplication passed for the Module 8 migration.
- Permission seeder idempotency passed.
- Production-mode seeding inserted no users, payments, reminders or templates.
- Composer validation passed.
- Pint style check passed.
- Frontend production build passed.
- Live unauthenticated credit access redirected to login.
- Live authorized credit access returned HTTP 200.
- Live CSRF rejection returned HTTP 419.
- Desktop, tablet and mobile browser visual review passed with no horizontal overflow.

Known limitations:

- The notification channel is local/test only and records `external_send=false`.
- Real SMS/WhatsApp adapters require provider approval and credentials in a later module.
- Dashboard reminder widgets are deferred to Module 11.
- Opening credit balances are deferred to Module 12 imports.

Acceptance status: Tested, not Approved.
