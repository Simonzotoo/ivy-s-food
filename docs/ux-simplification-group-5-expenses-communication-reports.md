# Workflow Simplification - Group 5: Expenses, Communication, Reports

Date: 21 August 2026

## Scope audited

Record expense, Expense history, Customer messages, Message templates, Reports, Filters, Exports.

## Usability problems found

1. **A real, pre-existing bug**: `resources/views/broadcasts/partials/form-fields.blade.php` opened with `@php($source = $previewMode ? $preview : old())` - Blade's single-line `@php(...)` shorthand compiled to an unterminated PHP block with no closing tag, which made the whole "Create Broadcast" page throw a 500 error once the view cache was cleared (a stale cached compile had been masking it). Found because editing this file forced a recompile in this session; confirmed pre-existing since I hadn't touched that line and no other file uses the same shorthand.
2. **Expense and broadcast statuses were plain text**, not badges, in both list and detail views.
3. **No confirmation before approving/rejecting an expense or "logging" (sending) a broadcast** - each is a one-click action with real consequences (a financial decision, or a real customer message going out for SMS/WhatsApp once a provider is connected).
4. **Broadcast "Filters" panel showed raw `json_encode(...)` output** in a `<pre>` block instead of plain language.
5. **Broadcast create form mixed 8 fields at one level**, most of them optional audience-segmentation filters that should be secondary to the actual name/channel/message.
6. **Expense "Receipt reference" and reminder-template fields relied on placeholder text only**, with no real labels.

## What changed

- **Fixed the broadcast-creation 500 error** by replacing the broken `@php(...)` shorthand with a standard `@php ... @endphp` block.
- **Expenses**: status badges (pending/approved/rejected) on both list and detail views; approve/reject now confirm first via the shared modal; receipt-reference fields moved under a labeled "More details" section.
- **Broadcasts**: create form reduced to three essential fields (name, channel, message) with template/schedule/audience-segmentation filters under "More details"; "Log now" relabeled "Mark as sent" with an honest confirmation message (this channel doesn't deliver real messages until a provider is connected - the confirmation says so rather than implying otherwise); the raw JSON filters panel replaced with a plain-language "Who this was sent to" list.
- **Credit/expense/broadcast tests** all still pass unchanged - only presentation and the one genuine bug fix.

## Tests

- Focused: `Module9ExpensesTest` (unchanged assertions) and `Module10BroadcastingTest` (unchanged assertions, but now actually exercises the fixed broadcast-create page) - 16 tests combined, all passing.
- Full regression suite: 108 tests, 542 assertions, passed.

## Verification

- Pint style check passed. Frontend build passed.
- Live browser walkthrough: Create Broadcast now renders correctly with the simplified essential fields and collapsed "More details"; Expenses list shows a green "Approved" badge from seeded demo data; Reports page unchanged (already reasonably clean, left as-is).
- No console/page errors during any of the above.

## Note

Reports (`reports/index.blade.php`) was audited and left largely as-is - it already presents filters, one report table and CSV/PDF export links without extra chrome, which matches the brief's "without pretending they are formal accounting statements" guidance reasonably well already.
