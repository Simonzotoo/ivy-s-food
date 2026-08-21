# UX Simplification Audit

Date: 21 August 2026

## Takeover context

- Repository HEAD at takeover: `ebd32b5` ("Add dashboard analytics charts"), nine commits past the previously-known UI-fix commit `4fcab07`.
- A full sidebar/label/dashboard simplification was already built and tested in commit `0c20cc8` ("Simplify staff-facing UX and navigation"), then fully reverted one commit later in `5b036f5` with no recorded reason. The four commits after the revert moved in the opposite direction, culminating in dashboard analytics/donut/bar charts.
- Decision: redo the simplification against current HEAD and remove the chart-heavy analytics section, consistent with this round's brief ("avoid excessive charts and technical statistics"). No prior business logic, permission checks, or calculations were changed.

## Findings

- The sidebar exposed internal modules as one long flat list; administration, imports and audit tooling competed visually with daily sales/production/inventory work.
- Several labels used system language instead of staff language (Product Catalogue, Credit Invoices, Stock Movements, Broadcasts, Stock Adjustments, Material Stock Movements).
- The dashboard mixed the figures staff actually need daily (today's sales, cash/MoMo received, credit sales, customers owing, low stock, expiring stock, expenses) with bar/donut charts, trend panels and multiple "recent activity" columns, which made the first screen busy without adding daily-use value.
- Key staff jobs (New Sale, Add Customer, Record Production, Add Purchase, Receive Credit Payment, Record Expense, Check Stock, Import Data) existed as separate pages but were not gathered as one quick-actions row.
- Advanced features (users, roles, imports, company settings, audit logs) were as visually prominent as daily work.

## Changes made

- Sidebar reorganized into flat daily-use links (Home, Sales, Customers, Reports) plus permission-aware expandable groups (Production, Inventory, Purchases, Credit, Expenses) and secondary groups (Communication, Data Imports, Administration, Settings), matching the brief's business-area structure.
- Dashboard rebuilt around 8 priority tiles (Today's Sales, Cash Received, MoMo Received, Credit Sales, Customers Owing, Low Stock, Products Expiring Soon, Today's Expenses) and a permission-aware quick-actions row. All figures reuse the existing `OperationalDashboardService` calculations unchanged; only three additive, non-destructive count fields were introduced (`nearExpiryCount`, `expiredCount` unused-removed, `customersOwingCount`) to support accurate tile counts without altering any existing total.
- Removed the sales-trend bar chart, payment-mix donut chart, top-products bar chart and the multiple "recent sales/purchases/production" columns from the dashboard; replaced with a single "View reports" link, since that data remains available in Reports unchanged.
- Renamed visible labels only (routes, controllers, models, table/column names untouched): Product Catalogue → Products and Sizes, Credit Invoices → Customers Owing, Stock Movements → Stock History, Material Stock Movements → Materials Used, Stock Adjustments → Adjust Stock, Broadcasts → Customer Messages, Create Production Batch → Record Production, Create Purchase → Add Purchase, "Receive goods" → Receive Purchase.

## Validation

- Focused tests: Module 1, Module 2 and Module 11 suites — 29 tests, 171 assertions, passed.
- Full regression suite: 95 tests, 470 assertions, passed.
- Pint style check passed. Frontend production build passed.
- Live browser check (Playwright, headless Chromium) at 1440px, 834px and 390px widths: sidebar renders as grouped/collapsible sections, dashboard shows the 8 requested tiles and quick actions, no console errors, no horizontal overflow.
- Fixed an unrelated pre-existing test fragility in `Module11DashboardReportsTest`: the dashboard fixture hardcoded `sale_date`/`due_date`/`incurred_on` to a literal `2026-08-20`, which silently zeroed every "today" metric once the date rolled over. Replaced with relative `now()` dates.

## Preserved controls

- No modules removed. No business services replaced. No permission checks weakened. No audit controls removed. No stock, credit, payment or reporting calculations changed. No module marked Approved.

## Not yet done

- Per-page "More details" collapsing of optional fields on long forms (purchases/production create forms) — next iteration.
- Sub-navigation tabs within business-area pages (e.g. Production Plans vs. Recipes) currently live only in the sidebar disclosure; still simple, but could be promoted to in-page tabs later if staff find the sidebar nesting confusing in practice.
