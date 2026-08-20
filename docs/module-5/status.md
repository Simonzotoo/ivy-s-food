# Module 5 Status

Status: Tested

Implementation commit: Pending

Scope:

- Immutable stock-movement ledger.
- Raw-material balances.
- Finished-product balances.
- Batch-level finished stock.
- Available, reserved, damaged, expired and quarantined quantities where applicable.
- Earliest-expiry-first allocation.
- Low-stock and near-expiry alerts.
- Damage and spoilage.
- Samples and internal use.
- Stock adjustments.
- Physical stocktakes.
- Variance review and approval.
- Inventory valuation based on documented cost, not selling price.
- Permissions and audit history.

Business rules:

- Normal operations must not produce negative stock.
- Users cannot overwrite stock balances directly.
- Finished product stock comes from completed production output records.
- Finished stock allocation uses earliest-expiry-first ordering.
- Inventory valuation uses production/material cost, never selling price.

Database changes:

- `finished_product_batches`
- `finished_stock_movements`
- `stock_adjustments`
- `stocktakes`
- `stocktake_items`

Routes:

- `GET /inventory`
- `GET /inventory/finished-batches`
- `GET /inventory/movements`
- `GET /inventory/adjustments`
- `POST /inventory/adjustments`
- `POST /inventory/production-outputs/{output}/post`
- `GET /inventory/stocktakes`
- `GET /inventory/stocktakes/create`
- `POST /inventory/stocktakes`
- `POST /inventory/stocktakes/{stocktake}/approve`

Permissions:

- `inventory.view`
- `inventory.adjust`
- `inventory.stocktake`
- `inventory.approve_stocktake`
- `inventory.post_production`

Verification:

- Focused Module 5 tests passed: 7 tests, 32 assertions.
- Full regression suite passed: 43 tests, 190 assertions.
- Migration fresh/seeding passed.
- Migration rollback and reapplication passed for the Module 5 migration.
- Permission seeder idempotency passed.
- Production-mode seeding inserted no users, finished batches, stocktakes, materials or suppliers.
- Composer validation passed.
- Pint style check passed.
- Frontend production build passed.
- Live unauthenticated inventory access redirected to login.
- Live authorized inventory access returned HTTP 200.
- Live CSRF rejection returned HTTP 419.
- Desktop, tablet and mobile browser visual review passed with no horizontal overflow.

Known limitations:

- Sales allocation APIs reserve stock by earliest expiry, but live sale deduction belongs to Module 7.
- Opening balances must be imported later as auditable opening-balance movements, not direct edits.
- Expiry and low-stock alert thresholds remain configurable and pending business confirmation.
- Stocktake forms support controlled batch counts; large import-based stocktakes are deferred to Module 12.

Acceptance status: Tested, not Approved.
