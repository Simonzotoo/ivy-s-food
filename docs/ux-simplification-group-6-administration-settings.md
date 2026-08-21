# Workflow Simplification - Group 6: Administration and Settings

Date: 21 August 2026

## Scope audited

Users, Roles and permissions, Company settings, Audit logs, Data imports, Import history, System settings.

## Approach

Unlike Groups 1-5, this group is explicitly meant to stay "available but visually separate from everyday operations" rather than be simplified down to a minimal daily-task flow - a permission matrix, an audit trail and company configuration are inherently detailed, administrator-facing tools. Objective 1's navigation rework already moved all of this out of the way of daily work (under Administration/Settings, collapsed by default). This pass applied only the fixes that were genuinely worth making without hollowing out the detail these screens need to do their job.

## What changed

- **Staff Accounts (Users)**: status now shown as a badge instead of plain text; Suspend/Activate now confirms first via the shared modal (suspending a colleague's account is a meaningful, immediate consequence - they can no longer sign in).
- **Company Settings**: the read-only Currency and Time zone fields (fixed for this deployment) now have a short note explaining why they can't be changed here, instead of just silently ignoring input with no explanation.

## Left as-is (with reasoning)

- **Roles & Permissions**: already organized into fieldsets by permission group; a permission matrix necessarily has to show every permission to be usable, so it wasn't simplified further.
- **Audit Logs**: the raw before/after JSON diff is intentional and useful for its audience (Owner, Manager, Auditor) doing an actual investigation; summarizing it away would remove the detail the page exists to provide.
- **Data Imports / Import history**: already substantially rebuilt in the earlier import-engine commit (`b4457c5`) - upload, mapping, dry-run preview, permission-gated commit, rollback.

## Tests

- Focused: `Module1FoundationTest` (16 tests) - all passing, unchanged assertions.
- Full regression suite: 108 tests, 542 assertions, passed.

## Verification

- Pint style check passed. Frontend build passed.
- Live browser walkthrough: Users list shows the active/suspended badge and the Suspend confirmation modal with a clear, specific message naming the affected user; confirmed the script only opened the modal and did not click through (to avoid locking out the account being tested with).
- No console/page errors during any of the above.

## Six-group sweep complete

This closes the six-group workflow simplification pass (Sales & Customers, Purchases & Production, Inventory, Credit & Payments, Expenses/Communication/Reports, Administration & Settings). Next: the full 20-step acceptance walkthrough covering customer creation/import, the purchase-to-sale-to-payment cycle, and dashboard/report review, per the brief.
