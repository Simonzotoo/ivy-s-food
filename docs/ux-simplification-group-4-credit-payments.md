# Workflow Simplification - Group 4: Credit and Payments

Date: 21 August 2026

## Scope audited

Customers owing, Credit invoice details, Due soon, Due today, Overdue, Receive payment, Partial payment, Customer statement, Reminder history.

## Usability problems found

1. **The Customer Statement feature had no link anywhere in the UI.** It's a real, working route (`credit.statement`) documented as a Module 8 feature, but nothing in the app ever pointed to it - it was only reachable by typing the URL directly with a customer ID.
2. **"Customers Owing" had no way to filter to due soon / due today / overdue / partially paid** even though those are exactly the categories the aging report already computes - the list only showed everything, unfiltered, with plain text instead of a status badge.
3. **No prominent "Receive Payment" action** on the main Customers Owing page - it was one click deeper, behind a small secondary "Payments" link.
4. **The payment form's customer dropdown** was a plain `<select>` despite showing each customer's outstanding balance, making it hard to find one customer among many.
5. **Reminder template form had no field labels**, relying on placeholder text alone (a known accessibility/usability issue - placeholder text disappears the moment you start typing).
6. **Aging report and invoice detail had no color coding or status badges** distinguishing overdue from not-yet-due, despite that being the entire point of the page.

## What changed

- **Customer statement** is now linked from both the credit invoice detail page and the customer detail page (gated by the existing `credit.reports` permission).
- **Customers Owing**: added search + status filter (not due / due soon / due today / overdue / partially paid / paid), status badges, and moved "Receive Payment" to a prominent primary button at the top of the page.
- **Receive Payment**: customer selector replaced with the searchable picker (shows each customer's outstanding balance as a sublabel), and added a short explanation of how a partial payment and the oldest-first allocation work - no changes to the underlying allocation logic itself.
- **Reminder templates**: proper field labels added.
- **Aging report**: color-accented borders per bucket (red for overdue, amber for due soon/today), and each listed invoice now links straight to its detail page.
- **Credit invoice detail**: status badge added next to the invoice number.

## Tests

- Focused: `Module8CreditTest` (5 tests) - all passing, unchanged assertions.
- Full regression suite: 108 tests, 542 assertions, passed.

## Verification

- Pint style check passed. Frontend build passed.
- Live browser walkthrough: Customers Owing shows the filter bar, status badge and Receive Payment button; the aging report renders its five color-accented buckets from real seeded data.
- No console/page errors during any of the above.

## Note

Recording a payment was deliberately left without an extra confirmation step (unlike sale reversal or production reversal) - it's a routine, frequent action for an Accounts Officer, already requires deliberate form entry, and is fully reversible in the sense that it's just a normal recorded transaction, not a destructive one. Adding a modal here would add friction to a core daily task without a matching safety benefit.
