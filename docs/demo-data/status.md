# Demo Data

Date: 20 August 2026

Status: Local demo data added

## Purpose

Populate the local Ivy's Food system so the simplified UX can be demonstrated with realistic-looking operational activity.

## Command

```bash
php artisan db:seed --class=DemoDataSeeder
```

## Data Added

- Demo supplier.
- Demo raw and packaging materials.
- One received purchase with raw-material batches.
- Three active demo product/size records with prices and barcodes.
- Approved demo recipes.
- Three completed production batches posted to finished stock.
- Three demo customers.
- Retail, wholesale and distributor/credit sales.
- Partial credit repayment.
- One approved expense.

## Safety

- The seeder is explicit and is not called by `DatabaseSeeder`.
- It is idempotent and skips if demo sales or expenses already exist.
- It refuses to run in `production`, including when `--force` is supplied.
- Demo records use `IVY_DEMO_DATA` markers where practical.

## Visual Evidence

- `docs/demo-data/visual-review/demo-home-desktop.png`
- `docs/demo-data/visual-review/demo-sales-desktop.png`
- `docs/demo-data/visual-review/demo-inventory-mobile.png`
