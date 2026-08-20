# Ivy's Food Module Roadmap

This is the authoritative module status file. Only the project owner may set any module to `Approved`.

| Module | Name | Status | Commit | Notes |
| --- | --- | --- | --- | --- |
| 1 | Application Foundation, Authentication, Users, Roles, Permissions, Company Settings and Audit Foundation | Tested | `240e8bf438029a01b269552a925b84c3c8e60fe1`, correction `1781876` | Not approved. Role-assignment guard hardened after baseline audit. |
| 2 | Product Catalogue and Variants | Tested | `8fc2ebc` | Product categories, official types/flavours, sizes, units, draft variants, SKU/barcode/price/status controls. |
| 3 | Raw Materials, Packaging, Suppliers and Purchases | Tested | `bd811aa` | Configurable materials, suppliers, purchases, receiving, returns and material stock movements. |
| 4 | Recipes and Production | Tested | `40a5303` | Versioned recipes, production batches, usage, output, wastage and costing estimates. |
| 5 | Inventory, Batches, Expiry and Stocktaking | Tested | `2865984` | Immutable stock ledger, balances, batch/expiry controls and stocktakes. |
| 6 | Customers, Customer Types and Pricing | Tested | `387a954` | Customer records, normalized contacts, pricing defaults, credit configuration. |
| 7 | Sales, POS, Receipts, Invoices and Returns | Tested | `edb63c6` | Barcode-assisted sales, payments, stock deduction, receipts, invoices and reversals. |
| 8 | Credit, Payments and Thirty-Day Reminders | Tested | `0b7f5b6` | Credit lifecycle, allocations, statements, aging and provider-neutral reminders. |
| 9 | Expenditure Management | Tested | `375a89c` | Expense categories, records, approvals, receipt references and operational reporting. |
| 10 | Broadcasting and Customer Communication | Tested | `4afe3bd` | Segmentation, templates, consent, opt-out and provider-neutral local/test delivery. |
| 11 | Dashboard and Reports | Planned | Pending | Data-backed operational dashboard, reports, exports and filters. |
| 12 | Data Migration and Configuration Readiness | Planned | Pending | Import templates, dry-runs, validation, duplicate detection and reconciliation. |
| 13 | Security, Performance, Deployment and Final Acceptance | Planned | Pending | Full security/performance/deployment review and final acceptance checklist. |

Roadmap basis: requirements document dated 18 August 2026 plus the controlled engineering sequence authorized on 20 August 2026.
