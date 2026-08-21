# Module 12 Pending Information

- Final cleaned import workbooks for the initial real data load.
- Physical stock count reconciliation before any opening-stock import is committed against production data.
- Existing barcodes to load via the barcode import.
- Opening stock valuation confirmation.
- Customer opening credit balance confirmation.
- Confirmation of the file retention window (currently 30 days after a batch reaches a terminal state) - adjust `imports:prune --days=` if the business needs a different period.
- Decision on whether new product-catalogue *creation* via import (not just updates to existing SKUs) is needed, and if so, who approves new SKU/category/pricing combinations before they go live.
- Whether an "Alternative phone number" field should be added to the customer record - the current schema and import only support one phone number per customer.
