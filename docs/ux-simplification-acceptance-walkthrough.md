# Full Acceptance Walkthrough

Date: 21 August 2026

Performed live in a real browser (Playwright, headless Chromium) against a freshly seeded local SQLite database (base seeders only, then reset again afterward), signed in as the Owner account unless noted. All 20 steps completed successfully; every figure was cross-checked against the database and reconciled exactly.

| # | Step | Result |
| --- | --- | --- |
| 1 | Add a customer manually | Created "Acceptance Manual Customer" via the simplified create form. |
| 2 | Import customers from CSV | Uploaded a 1-row CSV through the redesigned import wizard (upload -> map -> preview -> confirm); customer created. |
| 3 | Import customers from XLSX | Same wizard, a hand-built `.xlsx` file; read correctly by the dependency-free XLSX reader. |
| 4 | Add a supplier | Created "Acceptance Test Supplier". |
| 5 | Add a raw material | Created "Acceptance Test Milk" (base unit: Litre). |
| - | *(setup)* Activate and price a product | Surfaced the catalogue's own validation message ("needs SKU and all three prices") - fixed by supplying both, confirming the guard rail works as designed. |
| 6 | Record and receive a purchase | Bought 100 L of milk at GHS 2.50/L from the new supplier; received it with a lot number and expiry date, creating a material batch. |
| 7 | Create and approve a recipe | Draft recipe for the product using 10 L of milk per batch; approved via the confirmation modal. |
| 8 | Record a production batch | Planned batch of 40 units against the approved recipe. |
| 9 | Confirm raw-material deduction | Completed the batch (38 accepted, 2 rejected); material stock movement recorded `-10.000` L, available balance dropped 100 -> 90. |
| 10 | Confirm finished-product stock increase | Finished product batch created with `quantity_available = 38.000`; posted to inventory. |
| 11 | Check batches and expiry | Confirmed on the Finished Batches and Inventory pages. |
| 12 | Complete a retail sale | 2 units at GHS 10.00 (retail price) = GHS 20.00, paid in cash. |
| 13 | Complete a wholesale sale | 2 units at GHS 9.00 (wholesale price) = GHS 18.00, paid in cash. |
| 14 | Complete a distributor sale | 2 units at GHS 8.00 (distributor price) = GHS 16.00, paid in cash. |
| 15 | Print or preview a receipt | Opened the retail sale's receipt - correctly branded, itemized, GHS 20.00 total, GHS 0.00 balance. |
| 16 | Complete a credit sale | 3 units to "Acceptance Manual Customer", no cash/MoMo entered - GHS 30.00 went fully to credit; credit invoice created. |
| 17 | Receive a partial credit payment | GHS 10.00 cash payment recorded against the customer. |
| 18 | Check the remaining balance | Customer's outstanding balance correctly shows GHS 20.00 (30.00 - 10.00). |
| 19 | Record an expense | Created an expense category, then a GHS 15.00 expense (status: pending review). |
| 20 | Review the dashboard and reports | Home dashboard: Today's Sales GHS 84.00 (4 sales), Cash Received GHS 54.00, Credit Sales GHS 30.00, Customers Owing GHS 20.00, Today's Expenses GHS 0.00 (correctly excludes the still-pending expense - the tile is explicitly labeled "Approved expenses today"). Reports -> Daily sales confirms 4 sales / GHS 84.00 for the day, matching exactly. |

## Role-based navigation spot check

A fresh Cashier / Sales Officer account was created and checked on a 390px-wide (mobile) viewport: the sidebar showed only Home, Sales, Customers and Settings - no Production, Inventory, Purchases, Credit, Expenses, Reports, Communication, Data Imports or Administration. A direct request to `/users` correctly returned 403.

## Issues found and fixed during the walkthrough

Two script-side mistakes (wrong dropdown index; a too-generic button selector that matched the layout's "Sign out" button before the intended form button) were found and corrected in the test scripts themselves - not product bugs. They're noted here only because chasing them down is what surfaced the following two genuine, permanent fixes already covered in the group commits:

- The POS sale-type dropdown's unsafe alphabetical default (Distributor before Retail) - fixed in the Group 1 commit.
- The sitewide mobile sidebar-visibility bug and the broadcast-creation 500 error - fixed in the Objective-1 and Group 5 commits respectively.

No new product defects were found in this final walkthrough itself - every step worked on the first genuinely-correct attempt once the scripts matched the real form structure.

## Verification

- Full regression suite: 108 tests, 542 assertions, passed (after resetting the database used for this walkthrough back to a clean demo state).
- Pint, `composer validate --strict`, `composer audit` (no advisories), frontend build, and a migration rollback/reapply check all passed immediately afterward.
- Working tree clean; database reset to the standard seeded + demo-data state before handing back.
