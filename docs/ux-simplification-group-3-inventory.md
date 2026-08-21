# Workflow Simplification - Group 3: Inventory

Date: 21 August 2026

## Scope audited

Current stock, Stock history, Batches, Expiry, Low stock, Damaged/spoiled stock, Adjust stock, Stocktaking, Stocktake variance.

## Usability problems found

1. **The main Inventory page didn't answer three of the four questions it's supposed to.** `InventoryController::index()` already computed `nearExpiryFinished` and `expiredFinished`, but the view never rendered them - so "what's expiring soon" was invisible on the one page meant to show it. There was no low-stock view and no damaged/quarantined view at all, despite the data (`reorder_level`, `quantity_damaged`, `quantity_quarantined`) already existing.
2. **A real data-integrity risk in both the Adjust Stock and Stocktake forms**: raw-material batches and finished-product batches were mixed into one `<select>`, while a *separate* `stock_type` field determined which table to look the chosen ID up against. Since the two batch tables have independent auto-increment IDs, picking "Finished: ..." from the list while `stock_type` was still set to "Raw" (or vice versa) would look up the wrong record, or the wrong batch entirely if the IDs happened to coincide. This was easy to trigger by hand and had no client-side guard.
3. **Adjustment type labels were raw enum values** (`internal_use`, `release_quarantine`) with no explanation of what each means.
4. **Stocktake form always showed 6 blank batch rows** and mixed raw/finished batches in one list regardless of the stocktake's declared type.
5. **No confirmation before approving stocktake variances**, which directly adjusts stock to match the count.

## What changed

- **Inventory home page rebuilt** around the four questions from the brief: four summary tiles (Low stock, Expiring soon, Already expired, Damaged/quarantined) plus matching detail sections, all above the existing raw/finished balance lists (kept as the secondary, complete-picture view). Low-stock detection reuses the same reorder-level logic already used on the dashboard and the materials list.
- **Adjust Stock**: replaced the two-field (type + mixed dropdown) combination with a single searchable batch picker whose selection sets both the batch and its type together, making a mismatch impossible. Adjustment types now show plain-language labels ("Increase (found extra stock)", "Put in quarantine", etc.) instead of raw enum text.
- **New Stocktake**: dynamic add/remove batch rows (starts with one, not six); the batch picker is now scoped to whichever stock type (raw/finished) is selected at the top, and switching that type clears the rows rather than leaving stale, mismatched selections in place.
- **Stocktakes list**: "Approve variances" now confirms first (in-app modal, not a native dialog), and status uses the shared badge styling.

## Tests

- Focused: `Module5InventoryTest` (7 tests) - all passing, unchanged assertions (server-side adjustment/stocktake logic untouched; only the surrounding form/page changed).
- Full regression suite: 108 tests, 542 assertions, passed.

## Verification

- Pint style check passed. Frontend build passed.
- Live browser walkthrough: Inventory home page shows real low-stock/expiring/expired/damaged counts and lists from the seeded demo data; the Adjust Stock searchable batch picker correctly lists only-available batches with their current quantity; the redesigned stocktake form renders correctly.
- No console/page errors during any of the above.
