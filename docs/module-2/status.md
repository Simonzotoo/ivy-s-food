# Module 2 Status

Status: Tested

Implementation commit: `8fc2ebc`

Scope:

- Product categories.
- Official product types/flavours.
- Product sizes.
- Measurement units.
- Product variants.
- Internal SKUs.
- Existing barcodes.
- Retail, Wholesale and Distributor prices.
- Product image path support.
- Draft, Active and Inactive statuses.
- Reorder-level and shelf-life configuration.
- Duplicate SKU and barcode prevention.
- Product search, filtering and CSV export.
- Permission controls and audit logging.

Business rules:

- Official label spellings are preserved exactly.
- Confirmed categories are Fruit Juice, Yoghurt and Greek Yoghurt.
- Confirmed size/type combinations are seeded as Draft variants only.
- Prices, barcodes, shelf life and reorder levels remain blank until provided.
- Draft and incomplete variants cannot be used in live sales or production.
- Active variants require a SKU, retail price, wholesale price and distributor price.
- Barcodes and SKUs must be unique when present.

Database changes:

- `measurement_units`
- `product_categories`
- `product_types`
- `product_sizes`
- `product_variants`

Routes:

- `GET /catalogue`
- `GET /catalogue/export`
- `GET /catalogue/variants/{variant}/edit`
- `PUT /catalogue/variants/{variant}`
- `PATCH /catalogue/variants/{variant}/status`

Permissions:

- `catalogue.view`
- `catalogue.create`
- `catalogue.edit`
- `catalogue.activate`
- `catalogue.export`

Verification:

- Focused Module 2 tests passed.
- Full regression suite passed.
- Migration rollback and reapplication passed.
- Product catalogue seeder idempotency passed.
- Production-mode seeding inserted no development users.
- Composer validation passed.
- Pint style check passed.
- Frontend production build passed.
- Live HTTP catalogue access passed.
- Live CSRF rejection returned HTTP 419.
- Desktop, tablet and mobile browser visual review passed.

Known limitations:

- Product image support is stored as a configurable path only. Upload handling is deferred to the file-upload security review.
- Prices, barcodes, reorder levels and shelf-life values are intentionally blank until supplied by the business.
- Draft variants are unavailable for future live sales and production workflows.

Acceptance status: Tested, not Approved.
