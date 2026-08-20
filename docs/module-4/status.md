# Module 4 Status

Status: Tested

Implementation commit: Pending

Scope:

- Versioned product recipes or Bills of Materials.
- Recipe raw-material ingredients.
- Recipe packaging requirements.
- Planned production.
- Production batches and batch-number generation.
- Production and expiry dates.
- Actual material usage.
- Finished output and rejected output.
- Wastage, yield and variance.
- Estimated batch and unit cost.
- Production completion and reversal controls.
- Permissions and audit history.

Business rules:

- Products without an approved recipe cannot be completed as live production.
- Recipes begin as Draft and must be approved by an authorized user before production completion.
- Material consumption uses available material batches and blocks negative stock.
- Finished output is recorded for later inventory-module ingestion; it does not become sellable finished stock until Module 5.
- Unknown recipes, ingredient quantities, packaging needs and shelf-life values remain configurable and are not invented in seeders.

Database changes:

- `recipes`
- `recipe_materials`
- `production_plans`
- `production_batches`
- `production_material_usages`
- `production_outputs`

Routes:

- `GET /recipes`
- `GET /recipes/create`
- `POST /recipes`
- `GET /recipes/{recipe}`
- `POST /recipes/{recipe}/approve`
- `GET /production/plans`
- `GET /production/plans/create`
- `POST /production/plans`
- `GET /production/batches`
- `GET /production/batches/create`
- `POST /production/batches`
- `GET /production/batches/{batch}`
- `POST /production/batches/{batch}/complete`
- `POST /production/batches/{batch}/reverse`

Permissions:

- `recipes.view`
- `recipes.manage`
- `recipes.approve`
- `production.view`
- `production.plan`
- `production.start`
- `production.complete`
- `production.reverse`

Verification:

- Focused Module 4 tests passed: 7 tests, 33 assertions.
- Full regression suite passed: 36 tests, 158 assertions.
- Migration fresh/seeding passed.
- Migration rollback and reapplication passed for the Module 4 migration.
- Permission seeder idempotency passed.
- Production-mode seeding inserted no users, recipes, production batches, materials or suppliers.
- Composer validation passed.
- Pint style check passed.
- Frontend production build passed.
- Live unauthenticated recipe access redirected to login.
- Live authorized recipe and production access returned HTTP 200.
- Live CSRF rejection returned HTTP 419.
- Desktop, tablet and mobile browser visual review passed with no horizontal overflow.

Known limitations:

- Finished output is recorded as `pending_inventory` and is not sellable stock until Module 5 creates the finished-inventory ledger.
- Recipe quantities and packaging requirements are configurable but not seeded because the business has not supplied recipes.
- Batch-number generation uses a local sequential default; production-grade concurrency hardening remains on the Module 13 review list.
- Recipe material usage supports direct or inverse configured unit conversions. Compound conversion chains are not implemented yet.

Acceptance status: Tested, not Approved.
