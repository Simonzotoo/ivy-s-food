# How to Import Customers

This guide is for staff who have been given permission to import customers - normally an Accounts Officer or Manager.

## 1. Download the template

Go to **Data Imports** in the sidebar, find the **Customers** card, and click **Download template**. Open it in Excel or Google Sheets.

The template has:

- One heading row - do not change the column names.
- One example row marked `EXAMPLE` - delete or replace it before importing.
- Notes below the data explaining accepted values (customer types, sales types, Yes/No fields, date format).

Fill in one row per customer. Only **Customer name** and **Customer type** are required - everything else is optional.

## 2. Upload your file

Click **Upload file** on the Customers card. Choose your completed `.csv` or `.xlsx` file (up to 5 MB) and click **Upload and continue**.

## 3. Check the column matching

The system tries to match your file's columns to the right fields automatically. Check the "Match Columns" screen - if a column looks wrong, pick the correct one from the dropdown, or choose "Not in this file" if you left a column out.

If some of your customers already exist in the system (matched by phone number or email), choose what should happen:

- **Stop and review** (safest, selected by default) - the import will not proceed if it finds any matching customers, so you can check them first.
- **Skip duplicate rows** - matching rows are left alone; only genuinely new customers are added.
- **Update the matching customer record** - matching rows update the existing customer's details instead of creating a new one.

Click **Preview import**.

## 4. Review the preview

Before anything is saved, you will see:

- How many rows are valid, invalid, duplicate, new, or updates.
- A list of any row errors, with a downloadable error file so you can fix your spreadsheet and try again.

Nothing is saved to the system yet at this point.

## 5. Confirm

If you are happy with the preview, click **Confirm and import**, then confirm again in the dialog that appears. This is the only step that actually creates or updates customer records.

## Notes

- Phone numbers are automatically standardized (024xxxxxxx and +233xxxxxxxxx are treated as the same number).
- Opening credit balances are **not** part of this import - they are handled separately by an Accounts Officer through the "Open credit balances" import, with its own review step.
- If you made a mistake, go to the import's history entry and use **Roll back** - this only removes customers your import created, and only if nothing else (a sale, a credit invoice) has happened to them since.
