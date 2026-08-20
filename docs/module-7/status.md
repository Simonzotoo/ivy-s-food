# Module 7 Status

Status: Tested

Implementation commit: Pending

Scope:

- Barcode-assisted POS.
- Manual product search.
- Customer selection.
- Retail, Wholesale and Distributor sales.
- Correct price selection.
- Quantity validation.
- Discounts with permission.
- Cash, MoMo and Credit.
- Split and partial payments.
- Stock deduction from eligible batches.
- Sales history.
- Receipt generation.
- Credit invoice generation.
- Delivery notes.
- A4 and thermal-print-friendly layouts.
- PDF download.
- Receipt reprinting.
- Returns.
- Sale cancellation or reversal.
- Permission controls and audit logging.

Business rules:

- Completed sales use database transactions.
- Completed financial records are reversed or returned, not silently edited or deleted.
- Stock is deducted from eligible finished batches using earliest-expiry-first allocation.
- Credit sales require a customer.
- Discounts require explicit permission.

Database changes:

- `sales`
- `sale_items`
- `sale_item_batches`
- `sale_payments`
- `credit_invoices`
- `delivery_notes`
- `sale_returns`
- `sale_return_items`

Routes:

- `GET /sales`
- `GET /sales/pos`
- `POST /sales`
- `GET /sales/{sale}`
- `GET /sales/{sale}/receipt`
- `GET /sales/{sale}/{document}.pdf`
- `POST /sales/{sale}/reverse`
- `POST /sales/{sale}/returns`

Permissions:

- `sales.view`
- `sales.create`
- `sales.discount`
- `sales.return`
- `sales.reverse`
- `sales.reprint`

Verification:

- Focused Module 7 tests passed: 7 tests, 40 assertions.
- Full regression suite passed: 57 tests, 254 assertions.
- Migration fresh/seeding passed.
- Migration rollback and reapplication passed for the Module 7 migration.
- Permission seeder idempotency passed.
- Production-mode seeding inserted no users, sales, customers or finished stock.
- Composer validation passed after adding `dompdf/dompdf`.
- Pint style check passed.
- Frontend production build passed.
- Live unauthenticated sales access redirected to login.
- Live authorized sales access returned HTTP 200.
- Live CSRF rejection returned HTTP 419.
- Receipt PDF route returned a valid PDF response.
- Desktop, tablet and mobile browser visual review passed with no horizontal overflow after fixing mobile sale-detail cards.

Known limitations:

- PDF generation uses pure-PHP Dompdf for shared-hosting compatibility.
- The HTML receipt remains print-friendly; exact thermal printer width should be confirmed with the actual device.
- Sales with returns cannot be fully reversed to prevent double restoration of stock.
- Credit aging, allocations and repayment lifecycle are expanded in Module 8.
- MoMo integration records references only; no provider is contacted.

Acceptance status: Tested, not Approved.
