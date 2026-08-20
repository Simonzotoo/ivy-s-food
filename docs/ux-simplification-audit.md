# UX Simplification Audit

Date: 20 August 2026

Status: Implemented as a focused presentation redesign. No business logic, calculations, authorization rules or audit controls were removed.

## Findings

- The sidebar exposed internal modules as one long list, which made daily tasks compete with administration, imports and audit logs.
- Several labels used system language instead of staff language, such as Product Catalogue, Movements, Credit Invoices and Broadcasts.
- The dashboard showed useful data, but it mixed primary daily figures with secondary trend and recent-record panels, making the first screen feel busy.
- Key staff jobs, such as New Sale, Add Purchase, Record Production, Receive Credit Payment, Record Expense and Check Stock, were available but not gathered as quick actions.
- Purchasing, sales and production forms showed optional notes or attachment/reference fields at the same visual level as required transaction fields.
- Dense tables and operational pages sometimes lacked a short explanation of what the page is for.
- Empty states were present, but many did not tell staff what to do next.
- Mobile navigation was usable after the sidebar logo fix, but the long ungrouped menu required too much scanning.
- Advanced features such as users, roles, imports, company settings and audit logs were too visually prominent for ordinary staff.

## UX Direction

- Organize navigation around daily business activities first: Home, Sales, Production, Inventory, Purchases, Customers, Credit, Expenses and Reports.
- Place occasional or administrative work under Communication, Administration and Settings.
- Keep permission checks intact so staff only see links they are allowed to use.
- Make the dashboard answer the questions ordinary staff ask first: sales today, cash/MoMo received, credit sales, customers owing, low stock, expiring stock and expenses.
- Add quick actions for common jobs and leave deeper reports behind links.
- Use plain business labels in navigation and headings while leaving database and route names unchanged.
- Move optional form content into collapsible "More details" sections where possible.
- Preserve Ivy's Food colours, logo and calm white/neutral workspace.

## Validation Focus

- Top-level navigation and grouped navigation remain permission-aware.
- Dashboard quick actions only appear when the current user has permission.
- Existing routes and tests continue to pass.
- Desktop, tablet and mobile sidebar screenshots are refreshed after the redesign.
