# Ivy's Food Management System

Laravel 13 modular monolith for Ivy's Food, a Ghana-based yoghurt and fruit juice manufacturer.

## Module Status

Modules 1 through 10 are `Tested`. Approval is reserved for the project owner.

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

## Module 2 Features

- Confirmed product categories, official types/flavours, product sizes and measurement units.
- Draft product variants for all confirmed possible size/type combinations.
- SKU, barcode, Retail, Wholesale and Distributor price controls.
- Draft, Active and Inactive statuses with activation safeguards.
- Reorder-level and shelf-life fields left blank until business confirmation.
- Catalogue search, filtering and CSV export.
- Catalogue permissions and audit logging.

## Module 3 Features

- Generic raw-material and packaging-material category foundation.
- Materials with base units, reorder settings and lot/expiry controls.
- Unit conversions for configurable material handling.
- Supplier management.
- Purchase records, purchase items and safe attachment references.
- Goods receiving into material batches/lots.
- Purchase returns with available-quantity validation.
- Material stock movements derived from receiving and returns.
- Materials, suppliers, purchases and stock-movement permissions with audit logging.

## Module 4 Features

- Versioned draft and approved recipes/Bills of Materials.
- Ingredient and packaging-material recipe lines.
- Production plans and production batches.
- Batch-number generation, production and expiry dates.
- Approved-recipe enforcement before production completion.
- Transactional material consumption from available material batches.
- Finished output, rejected output, wastage, yield, variance and estimated costing records.
- Production reversal controls that restore consumed material stock.
- Recipe and production permissions with audit logging.

## Module 5 Features

- Finished-product batch inventory posted from completed production output.
- Finished stock movement ledger.
- Raw-material and finished-product balance views.
- Available, reserved, damaged, expired and quarantined finished-stock quantities.
- Earliest-expiry-first finished-stock reservation service.
- Stock adjustments for raw and finished stock with negative-stock protection.
- Physical stocktakes with variance review and approval.
- Inventory valuation based on documented production/material cost.
- Inventory permissions and audit logging.

## Module 6 Features

- Customer types for Individual, Shop, Distributor, Mall / Supermarket and Organization.
- Separate Retail, Wholesale and Distributor sale/pricing types.
- Customer records with normalized Ghanaian phone numbers and duplicate detection.
- Customer status, approved credit limit and outstanding balance fields.
- Communication consent and local communication history.
- Purchase-history placeholder that will use live sales records after Module 7.
- Customer permissions and audit logging.

## Module 7 Features

- POS sales with barcode/manual product lookup.
- Retail, Wholesale and Distributor price selection from active product variants.
- Cash, MoMo and Credit payments, including split/partial payments.
- Finished-stock deduction from eligible batches using earliest-expiry-first ordering.
- Receipt, credit invoice and delivery note records.
- Shared-hosting-compatible PDF downloads using Dompdf.
- Sale returns and guarded sale reversal.
- Sales permissions and audit logging.

## Module 8 Features

- Credit invoice lifecycle statuses.
- Customer repayment recording with MoMo/reference fields.
- Automatic allocation to oldest outstanding credit invoices.
- Customer statements, payment receipt PDFs and credit-aging report.
- Reminder templates and reminder history.
- Provider-neutral local/test notification channel with no real external sends.
- Credit permissions and audit logging.

## Module 9 Features

- Configurable expense categories.
- Expense records with date, amount, payment method and description.
- Receipt reference fields without unsafe binary upload assumptions.
- Pending, approved and rejected expense statuses.
- Approval and rejection workflow with review trail.
- Operational expense report by period and category.
- Expense permissions and audit logging.

## Module 10 Features

- Broadcast templates for local, SMS and WhatsApp channels.
- Customer segmentation by customer type, default sale type, location and purchase history.
- Recipient preview before broadcast creation.
- Immediate and scheduled broadcasts.
- Consent and opt-out filtering.
- Local/test provider adapter that records `external_send=false`.
- Broadcast recipient delivery statuses and customer communication history.
- Broadcast permissions and audit logging.

## Roadmap

The authoritative module roadmap and status record is maintained in `docs/modules-roadmap.md`.

## Source Assets

Original stakeholder files are preserved in `docs/source/`. The application uses a copied logo at `public/images/ivys-logo.png`.
