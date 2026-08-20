# Module 3 Status

Status: Tested

Implementation commit: `bd811aa`

Scope:

- Raw-material and packaging-material categories.
- Materials with base units, reorder settings and lot/expiry configuration.
- Unit conversions.
- Supplier management.
- Purchase records and purchase items.
- Goods receiving into material batches/lots.
- Purchase attachments as safe stored paths.
- Purchase returns.
- Raw-material stock-in and stock-out movement ledger.
- Reorder alert fields.
- Permissions and audit history.

Business rules:

- Unknown materials and suppliers are configurable and are not invented in seeders.
- Purchases start as Draft and can be received by authorized users.
- Receiving a purchase creates material batches and immutable stock movements.
- Purchase returns cannot exceed available batch quantity.
- Material balances are derived from movement/batch records, not overwritten directly.
- Money is stored as fixed decimal values; no floating-point money arithmetic.

Database changes:

- `material_categories`
- `unit_conversions`
- `materials`
- `suppliers`
- `purchases`
- `purchase_items`
- `purchase_attachments`
- `material_batches`
- `material_stock_movements`
- `purchase_returns`
- `purchase_return_items`

Routes:

- `GET /materials`
- `GET /materials/create`
- `POST /materials`
- `GET /materials/{material}/edit`
- `PUT /materials/{material}`
- `GET /material-stock-movements`
- `GET /suppliers`
- `GET /suppliers/create`
- `POST /suppliers`
- `GET /suppliers/{supplier}/edit`
- `PUT /suppliers/{supplier}`
- `GET /purchases`
- `GET /purchases/create`
- `POST /purchases`
- `GET /purchases/{purchase}`
- `POST /purchases/{purchase}/receive`
- `POST /material-batches/{batch}/return`

Permissions:

- `materials.view`
- `materials.manage`
- `suppliers.view`
- `suppliers.manage`
- `purchases.view`
- `purchases.create`
- `purchases.edit`
- `purchases.receive`
- `purchases.return`
- `material_stock.view`

Verification:

- Focused Module 3 tests passed: 6 tests, 32 assertions.
- Full regression suite passed: 29 tests, 125 assertions.
- Migration fresh/seeding passed.
- Migration rollback and reapplication passed for the Module 3 migration.
- Material foundation seeder idempotency passed.
- Production-mode seeding inserted no users, materials or suppliers.
- Composer validation passed.
- Pint style check passed.
- Frontend production build passed.
- Live unauthenticated material access redirected to login.
- Live authorized material access returned HTTP 200.
- Live CSRF rejection returned HTTP 419.
- Desktop, tablet and mobile browser visual review passed with no horizontal overflow.

Known limitations:

- Purchase attachments are stored as safe document references only. Binary upload handling and file security hardening are deferred to the file-upload security review.
- Supplier balances and purchase payment settlement are deferred to later financial modules.
- Raw-material recipes and production consumption are deferred to Module 4 and Module 5.
- Purchase and purchase-return numbers use local sequential generation; concurrent production hardening will be revisited during the performance/security module.

Acceptance status: Tested, not Approved.
