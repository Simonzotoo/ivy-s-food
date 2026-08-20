# Module 10 Status

Status: Tested

Implementation commit: `4afe3bd`

Scope:

- Customer segmentation.
- Message templates.
- Message preview.
- Scheduled and immediate broadcasts.
- Communication consent and opt-out handling.
- Delivery status.
- Communication history.
- Provider-neutral SMS and WhatsApp adapters.
- Permissions and audit history.

Business rules:

- No real SMS or WhatsApp message is sent in development or testing.
- Broadcast recipients must be active customers with communication consent and no opt-out timestamp.
- Purchase-history filters use existing sales records where available.
- Provider delivery is local/test only until credentials and approval are supplied.

Database changes:

- `customers.communication_opted_out_at`
- `customers.communication_opt_out_reason`
- `broadcast_templates`
- `broadcasts`
- `broadcast_recipients`

Routes:

- `GET /broadcasts`
- `GET /broadcasts/create`
- `POST /broadcasts/preview`
- `POST /broadcasts`
- `GET /broadcasts/templates`
- `POST /broadcasts/templates`
- `GET /broadcasts/consent`
- `POST /broadcasts/customers/{customer}/opt-out`
- `GET /broadcasts/{broadcast}`
- `POST /broadcasts/{broadcast}/send`

Permissions:

- `broadcasts.view`
- `broadcasts.create`
- `broadcasts.send`
- `broadcasts.manage_templates`
- `broadcasts.manage_consent`

Verification:

- Focused Module 10 tests passed: 7 tests, 38 assertions.
- Full regression suite passed: 78 tests, 348 assertions.
- Migration fresh/seeding passed.
- Migration rollback and reapplication passed for the Module 10 migration.
- Permission seeder idempotency passed.
- Production-mode seeding inserted no users, broadcasts, templates or recipients.
- Composer validation passed.
- Pint style check passed.
- Frontend production build passed.
- Live unauthenticated broadcast access redirected to login.
- Live authorized broadcast access returned HTTP 200.
- Live unauthorized-role broadcast access returned HTTP 403.
- Live CSRF rejection returned HTTP 419.
- Desktop, tablet and mobile browser visual review passed with no horizontal overflow.

Known limitations:

- Real SMS/WhatsApp delivery requires provider approval and credentials.
- Final provider security and rate-limit review is deferred to Module 13.

Acceptance status: Tested, not Approved.
