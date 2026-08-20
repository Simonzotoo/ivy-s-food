# UX Simplification Redesign

Date: 20 August 2026

Status: Tested, not Approved

## Scope

- Reorganized the sidebar around ordinary daily work instead of internal module order.
- Grouped less frequent work under Communication, Administration and Settings.
- Simplified the Home dashboard around daily sales, received payments, credit sales, customers owing, stock alerts, expiry alerts and expenses.
- Added permission-aware quick actions for common jobs.
- Reworded key screens with plain business language while preserving existing routes, services, calculations and permissions.
- Added short page guidance and clearer empty states for major workflows.
- Moved optional notes and attachment/reference fields into "More details" on long forms.

## Key UI Changes

- `Dashboard` became `Home`.
- `Product Catalogue` became `Products and Sizes`.
- `Credit Invoices` became `Customers Owing`.
- `Credit Payments` became `Receive Payment`.
- `Stock Movements` became `Stock History`.
- `Broadcasts` became `Customer Messages`.
- `Create Purchase` became `Add Purchase`.
- `Create Production Batch` became `Record Production`.

## Validation

- Frontend production build passed.
- Focused Module 1 tests passed with role-aware navigation assertions.
- Focused Module 2 catalogue tests passed with new plain-language labels.
- Full regression suite passed: 95 tests, 476 assertions.
- Pint style check passed.
- Composer validation passed.
- Live desktop, tablet and mobile visual checks passed with no horizontal overflow.

## Visual Evidence

- Before desktop Home: `docs/ux-redesign/visual-review/before-home-desktop.png`
- After desktop Home: `docs/ux-redesign/visual-review/after-home-desktop.png`
- After tablet Home menu: `docs/ux-redesign/visual-review/after-home-tablet-menu.png`
- After mobile Home menu: `docs/ux-redesign/visual-review/after-home-mobile-menu.png`
- After New Sale: `docs/ux-redesign/visual-review/after-sales-pos-desktop.png`
- After Add Purchase: `docs/ux-redesign/visual-review/after-purchase-create-desktop.png`
- After Inventory mobile: `docs/ux-redesign/visual-review/after-inventory-mobile.png`
- After Reports: `docs/ux-redesign/visual-review/after-reports-desktop.png`

## Preserved Controls

- No modules were removed.
- No business services were replaced.
- No permission checks were weakened.
- No audit controls were removed.
- No stock, credit, payment or reporting calculations were changed.
- No module was marked `Approved`.
