# Module 12 Status

Status: Tested

Implementation commits: `b9ed26e` (original), extended 21 August 2026 (customer-import safety rebuild)

Scope:

- Real file upload for CSV and XLSX (.xlsx), replacing the earlier paste-CSV-content flow.
- Downloadable templates with a marked example row, accepted-value notes, and required/optional guidance for every supported type.
- Automatic column-mapping suggestions with a manual override screen.
- Dry-run preview showing total/valid/invalid/duplicate/new/update/skipped row counts before anything is saved.
- Customer import: Ghanaian phone normalization, exact-and-likely duplicate detection (via normalized phone and email), and an explicit duplicate strategy (Skip / Update / Stop and review - defaults to Stop, the safest option).
- Explicit in-app confirmation (no browser-native alert boxes) required before any commit.
- Granular permissions separating view, history, template download, upload, validation, per-type commit, and rollback.
- Row-level error reporting with a downloadable, formula-injection-safe error CSV.
- Opening stock imports create batch records and `opening_balance` stock movements; they never edit stock totals directly.
- Open credit balance imports create auditable opening sales and credit invoices, kept separate from the normal customer import.
- Rollback removes only import-created records; customers are only removed if they have acquired no sales or credit invoices since import (soft-deleted, never hard-deleted).
- Uploaded files are stored on the private `local` disk (`storage/app/private`, not web-accessible) with a SHA-256 checksum recorded on the batch. `imports:prune` (scheduled weekly) deletes the stored file for import batches in a terminal state older than 30 days; the audit record itself is never deleted.
- Malicious-file protections: extension allowlist (.csv/.xlsx only), 5 MB size cap, genuine-file signature check (rejects a text file renamed to .xlsx, or a non-CSV file renamed to .csv), a 20,000-row processing cap, and formula/CSV-injection-safe CSV output (leading `=`, `+`, `-`, `@` characters are neutralized on every downloadable CSV).
- No third-party spreadsheet library was added: `phpoffice/phpspreadsheet` (the standard option) currently ships nine open security advisories on every 4.x release per `composer audit`, so XLSX reading was implemented directly against PHP's built-in `ZipArchive`/`XMLReader` (read-only, cached-value-only - formulas are never evaluated).

Database changes:

- `import_batches`: added `original_filename`, `file_disk`, `file_path`, `checksum`, `mapping`, `duplicate_strategy`, `duplicate_rows`, `new_rows`, `update_rows`, `skipped_rows`, `confirmed_at`.

Routes:

- `GET /imports`, `GET /imports/templates/{type}`, `GET /imports/create/{type}`, `POST /imports`
- `GET /imports/{batch}/map`, `POST /imports/{batch}/map`
- `GET /imports/{batch}`, `GET /imports/{batch}/errors.csv`
- `POST /imports/{batch}/commit`, `POST /imports/{batch}/rollback`

Permissions (replacing the previous single `imports.run`):

- `imports.view`, `imports.history`, `imports.templates`, `imports.upload`, `imports.validate`
- `imports.commit_customers`, `imports.commit_suppliers`, `imports.commit_catalogue`, `imports.commit_opening_stock`
- `imports.rollback`

Verification:

- Focused Module 12 tests passed: 17 tests, 81 assertions (upload, mapping, CSV and XLSX parsing, invalid-row reporting, duplicate detection and all three strategies, rollback with dependency protection, disguised/oversized file rejection, formula-injection neutralization, legacy supplier/material/opening-stock/open-credit flows, permission enforcement, seeder idempotency).
- Full regression suite passed: 105 tests, 524 assertions.
- Migration rollback and reapplication passed for the new `import_batches` columns.
- Composer validation and `composer audit` passed (no advisories).
- Pint style check passed. Frontend production build passed.
- Live browser walkthrough (desktop and mobile) of upload -> column mapping -> preview -> in-app confirmation modal -> commit -> customer visible in Customers, with no horizontal overflow and no native `confirm()`/`alert()` dialogs.

Known limitations:

- Only the first worksheet of an uploaded .xlsx workbook is read.
- New product-catalogue *creation* (as opposed to updating existing SKUs) and recipe/BOM import remain out of scope, consistent with the requirement not to import recipes automatically without unambiguous ingredient/unit/yield mapping.
- Open-credit rollback is not automatic and still requires finance reconciliation policy, as before.

Acceptance status: Tested, not Approved.
