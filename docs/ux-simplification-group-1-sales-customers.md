# Workflow Simplification - Group 1: Sales and Customers

Date: 21 August 2026

## Scope audited

New Sale/POS, Sales history, Sale details, Receipts and invoices, Returns, Customer list, Add/edit customer, Customer details, Pricing selection.

## Usability problems found

1. **POS showed no price or total before submitting a sale.** The cashier picked a product and quantity blind - price is resolved server-side per sale type, but nothing on screen showed it. There was no running subtotal/total, so mistakes were only visible after completing the sale.
2. **POS always rendered 6 blank item rows** regardless of basket size, and used plain `<select>` dropdowns for product/customer with no search - unusable once the catalogue or customer list grows.
3. **Unsafe default sale type.** The sale-type list was ordered alphabetically (Distributor, Retail, Wholesale), so the form defaulted to **Distributor pricing** - roughly 20% cheaper than Retail - for every ordinary walk-in sale unless the cashier remembered to change it. Found via live browser testing, not by reading the code.
4. **A pre-existing bug that silently blocked every fully-paid walk-in sale.** The old form always submitted a "Credit" payment row (even with a blank/zero amount) alongside Cash and MoMo. `SalesService::createSale()` treats the mere presence of a Credit row as "this is a credit sale," so any cash-only sale without a selected customer was rejected with "Credit sales require a customer" - a bug that would have existed in the original form too, only surfaced now by actually completing a sale in the browser rather than only by direct API test calls.
5. **The dashboard's "Credit Sales" tile was always GHS 0.00.** It read from `SalePayment` grouped by method, but a credit sale never creates a `SalePayment` row for the "Credit" method (credit is a liability, not money received) - so the figure could never be anything but zero. Fixed by sourcing it from `CreditInvoice.issue_date` instead.
6. **No confirmation before returning an item or reversing a whole sale** - a single click executed either action immediately.
7. **Customer detail page's "Purchase history" was a hard-coded placeholder** ("will use live sales history after Module 7") even though Module 7 (Sales) has been complete and in use - customers' actual sales were never shown there.
8. **Sales list had no filters and no status badges**; customer status (active/inactive/suspended) was filterable but never actually shown in the table.
9. **A pre-existing, sitewide mobile bug**: the sidebar navigation drawer was visibly open by default on real mobile-width screens instead of hidden behind the "Menu" button - discovered only by checking the actual rendered/closed state (every previous screenshot in this project had explicitly opened the menu first). Root cause: Tailwind v4's percentage-based `translate`/`-translate-x-full` utilities computed their custom properties correctly but did not visibly move the element in this environment; switched the mobile show/hide mechanism to plain `left` positioning, which is unambiguous and verified working.

## What changed

- **POS rebuilt** (`resources/views/sales/pos.blade.php`, `SalesController::pos/index`): live per-line unit price and line total, a running subtotal/discount/total/"balance to credit" summary, searchable product and customer pickers (new reusable `<x-searchable-select>` component), dynamic add/remove item rows (starts with one, not six), safe default sale type (Retail, not alphabetically-first), "More details" for sale date/notes, entered data preserved on validation failure, and the spurious always-present Credit payment marker removed so cash/MoMo-only sales no longer require a customer.
- **Sale detail page**: return and reversal actions now go through a new reusable in-app confirmation modal (`<x-confirm-form>`, Alpine-based) instead of submitting immediately - no browser-native `confirm()` dialogs anywhere in the app.
- **Customer detail page**: "Purchase history" replaced with real recent sales (last 10), gated by the `sales.view` permission so roles without sales access (e.g. Marketing Officer) don't see it.
- **Customer form**: restructured to essential fields first (name, type, phone, pricing type, credit limit, status) with contact person/email/location/address/consent/notes under "More details." Fields affecting price, credit or financial accuracy were kept visible, per the simplification rule.
- **Sales list**: added search (sale number/customer) and sale-type/payment-status filters, status badges, and a clearer empty state.
- **Customers list**: added a status badge next to each customer's name (previously filterable but invisible).
- **Receipt/invoice/delivery-note template**: added visual styling (borders, spacing, brand-navy heading) for the on-screen preview; the separate Dompdf PDF template was already styled and is untouched.
- **Dashboard**: "Credit Sales" tile now reflects real credit extended today.
- **Sitewide**: mobile sidebar drawer fixed to actually stay closed until "Menu" is tapped.

## Tests added/updated

- `Module7SalesTest`: sales-index filters, dashboard credit-sales-tile correctness (17 tests, all passing).
- `Module6CustomersTest`: customer detail page shows recent sales, and hides them for roles without `sales.view` (8 tests, all passing).
- `Module11DashboardReportsTest`: unaffected by the tile source change (assertion was on label text, not the old broken value).

## Verification

- Focused tests (Sales + Customers): 17 + 8 = pass, 82+ assertions.
- Full regression suite: 108 tests, 542 assertions, passed.
- Pint style check passed. `composer validate --strict` passed. Frontend build passed.
- Live browser walkthrough (Playwright, headless Chromium): completed a full cash sale end-to-end (product search -> live pricing -> payment -> redirect to sale detail -> receipt view); confirmed the reverse-sale confirmation modal opens/cancels/confirms correctly and is not a native dialog; verified desktop (1440px) and mobile (390px) rendering with no horizontal overflow on POS, customers list, sales list, and customer create form.
- Two genuine bugs found only through this live testing (safe default sale type; spurious Credit-marker blocking cash sales) were fixed and re-verified in the same session.

## Not yet done (deferred to later groups or follow-up)

- Receipts/invoices remain plain HTML/PDF without the raster logo image (Dompdf's `isRemoteEnabled: false` setting, a deliberate security control from Module 13, blocks fetching it as a URL; a text-based brand treatment was used instead).
- Sale returns/reversal forms were wrapped in the confirmation modal but not otherwise re-simplified this pass (they were already reasonably short forms).
