<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28px 34px; }
    body { font-family: 'Helvetica', Arial, sans-serif; font-size: 13px; color: #262b33; line-height: 1.5; }
    h1.cover-title { font-size: 26px; color: #2d4054; margin: 60px 0 6px 0; text-align: center; }
    p.cover-sub { font-size: 14px; color: #6b7280; text-align: center; margin: 0 0 4px 0; }
    p.cover-meta { font-size: 12px; color: #9ca3af; text-align: center; margin: 40px 0 0 0; }
    .cover-logo { text-align: center; margin-top: 70px; }
    .cover-logo img { height: 90px; width: 90px; object-fit: contain; }
    .cover-rule { border-bottom: 4px solid #d9110f; width: 120px; margin: 18px auto 0 auto; }
    h2.section-title {
        font-size: 17px; color: #d9110f; text-transform: uppercase; letter-spacing: 0.5px;
        margin: 0 0 10px 0; padding-bottom: 6px; border-bottom: 2px solid #d9110f;
    }
    h3.group-title { font-size: 14px; color: #2d4054; margin: 14px 0 4px 0; }
    p { margin: 0 0 8px 0; }
    ul { margin: 0 0 10px 0; padding-left: 18px; }
    li { margin-bottom: 4px; }
    .lede { font-size: 13px; color: #444b57; margin-bottom: 14px; }
    .divider { page-break-before: always; }
    .callout {
        background: #f7f8fa; border-left: 3px solid #d9110f; padding: 8px 12px; margin: 10px 0 14px 0; font-size: 12.5px; color: #4b5563;
    }
    table.role-table { width: 100%; border-collapse: collapse; margin: 8px 0 14px 0; font-size: 12.5px; }
    table.role-table th, table.role-table td { border: 1px solid #d8dee8; padding: 6px 8px; text-align: left; vertical-align: top; }
    table.role-table th { background: #f1f3f6; color: #2d4054; }
</style>
</head>
<body>

{{-- Cover --}}
@php $company = \App\Models\CompanySetting::current(); @endphp
<div class="cover-logo">
    @php
        $logoPath = $company->logo_path ? public_path($company->logo_path) : public_path('images/ivys-logo.png');
        $printLogo = is_file($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
    @endphp
    @if ($printLogo)
        <img src="data:image/png;base64,{{ $printLogo }}">
    @endif
</div>
<h1 class="cover-title">{{ $company->trading_name ?: "Ivy's Food" }} Management System</h1>
<p class="cover-sub">Feature &amp; Security Overview</p>
<p class="cover-sub">Prepared for demo presentation</p>
<div class="cover-rule"></div>
<p class="cover-meta">{{ now()->format('F j, Y') }}</p>

<div class="divider"></div>

{{-- System Overview --}}
<x-print-header title="System Overview" subtitle="What this system is" />

<p class="lede">
    Ivy's Food Management System is a purpose-built platform that brings every day-to-day
    operation of the business into one place: sales, customers, credit, inventory, production,
    purchasing, expenses, staff communication, and reporting. It works on any device with a web
    browser &mdash; till, office computer, tablet, or phone &mdash; and every screen is designed so
    staff can be productive with minimal training.
</p>
<p>
    Behind the scenes, the system keeps a complete and permanent record of every important action,
    so management always has full visibility into what is happening across the business, in real time.
</p>

<h3 class="group-title">Dashboard</h3>
<ul>
    <li>Real-time snapshot of today's sales, cash and Mobile Money received, credit issued, and expenses</li>
    <li>Automatic low-stock and expiring-stock alerts</li>
    <li>Visual analytics: sales trends and category breakdowns at a glance</li>
    <li>One-click shortcuts into the most common daily tasks</li>
</ul>

<x-print-footer />

<div class="divider"></div>

{{-- Feature Guide --}}
<x-print-header title="Feature Guide" subtitle="Sales, Customers &amp; Credit" />

<h3 class="group-title">Sales (Point of Sale)</h3>
<ul>
    <li>Fast till screen for ringing up sales with Retail, Wholesale, or Distributor pricing</li>
    <li>Cash, Mobile Money, or Credit payment on every sale</li>
    <li>Branded receipts &mdash; view, print, or download as PDF</li>
    <li>Full searchable sales history with date and customer filters</li>
    <li>Returns and reversals, with stock automatically restored</li>
</ul>

<h3 class="group-title">Customers</h3>
<ul>
    <li>Complete customer directory with contact details, credit terms, and full purchase history</li>
    <li>Communication log per customer</li>
    <li>Bulk customer import from Excel/CSV, with a safety preview before anything is saved</li>
</ul>

<h3 class="group-title">Credit Management</h3>
<ul>
    <li>Always know exactly who owes what, and since when</li>
    <li>Aging report grouping balances into Not Due, Due Soon, and Overdue</li>
    <li>Payments automatically allocate to the oldest outstanding invoice first</li>
    <li>Branded payment receipts (PDF)</li>
</ul>

<x-print-footer />

<div class="divider"></div>

<x-print-header title="Feature Guide" subtitle="Inventory, Production &amp; Purchasing" />

<h3 class="group-title">Inventory</h3>
<ul>
    <li>Live view of raw material and finished product stock levels</li>
    <li>Complete stock movement history &mdash; every addition and deduction is traceable</li>
    <li>Physical stocktake support: count what is on the shelf and the system calculates the difference</li>
    <li>Manual stock adjustments require a reason and are fully audited</li>
    <li>Expiry tracking with advance warning</li>
</ul>

<h3 class="group-title">Production &amp; Recipes</h3>
<ul>
    <li>Standard recipes (bill of materials) define exactly what ingredients go into each product</li>
    <li>Recording a production batch automatically deducts the ingredients used and adds the new finished stock</li>
    <li>Production planning for upcoming runs</li>
</ul>

<h3 class="group-title">Purchases, Materials &amp; Suppliers</h3>
<ul>
    <li>Record purchases from suppliers and receive stock into batches with expiry tracking</li>
    <li>Supplier directory with location, contact details, and full purchase history</li>
    <li>Raw material catalogue with reorder levels</li>
</ul>

<h3 class="group-title">Product Catalogue</h3>
<ul>
    <li>Complete product catalogue across all categories, types, and sizes</li>
    <li>Product photos for easy visual identification</li>
    <li>Separate pricing for Retail, Wholesale, and Distributor customers</li>
</ul>

<x-print-footer />

<div class="divider"></div>

<x-print-header title="Feature Guide" subtitle="Expenses, Communication &amp; Reporting" />

<h3 class="group-title">Expenses</h3>
<ul>
    <li>Record and categorise day-to-day business expenses</li>
    <li>Approval workflow &mdash; expenses can be approved or rejected before being finalised</li>
    <li>Expense reports by category and time period</li>
</ul>

<h3 class="group-title">Communication</h3>
<ul>
    <li>Send a message to customers by SMS, Email, or both at once, from a single screen</li>
    <li>Filter recipients by customer type, location, or purchase history</li>
    <li>Every customer's consent is respected &mdash; nobody is messaged without having agreed to it</li>
</ul>

<h3 class="group-title">Reports</h3>
<ul>
    <li>Sales, inventory, credit, and expense summaries in one place</li>
    <li>Export to CSV or branded PDF for sharing or record-keeping</li>
</ul>

<h3 class="group-title">Data Imports</h3>
<ul>
    <li>Bulk-import customers, suppliers, materials, products, opening stock, or credit balances from Excel/CSV</li>
    <li>Every import is validated and previewed before anything is saved</li>
    <li>Choose how duplicates are handled: skip, update, or stop</li>
    <li>Full import history, with the ability to roll back an import if something went wrong</li>
</ul>

<x-print-footer />

<div class="divider"></div>

<x-print-header title="Feature Guide" subtitle="Administration &amp; Access Control" />

<h3 class="group-title">Staff &amp; Roles</h3>
<ul>
    <li>Create, edit, suspend, or reactivate staff accounts</li>
    <li>Three roles: CEO, COO, and Supervisor</li>
    <li>Fine-grained permissions &mdash; the CEO decides exactly what each role can see and do, screen by screen, action by action, from the Roles &amp; Permissions page &mdash; no developer involvement needed to change access</li>
</ul>

<table class="role-table">
    <tr><th>Role</th><th>Access</th></tr>
    <tr><td><strong>CEO</strong></td><td>Full, unrestricted access to every part of the system</td></tr>
    <tr><td><strong>COO</strong></td><td>Configurable &mdash; permissions are assigned by the CEO as needed</td></tr>
    <tr><td><strong>Supervisor</strong></td><td>Configurable &mdash; permissions are assigned by the CEO as needed</td></tr>
</table>

<h3 class="group-title">Audit History</h3>
<ul>
    <li>A complete, searchable record of who did what and when, across the entire system</li>
    <li>Every sensitive action &mdash; sales, stock adjustments, credit payments, staff and permission changes &mdash; is permanently logged with before-and-after detail</li>
</ul>

<h3 class="group-title">Company Settings</h3>
<ul>
    <li>Company details (name, contact info, currency, tax details) used throughout receipts and reports</li>
    <li>Configurable business defaults: credit period, low-stock threshold, expiry alert window</li>
</ul>

<x-print-footer />

<div class="divider"></div>

{{-- Security --}}
<x-print-header title="Security &amp; Data Protection" subtitle="How the system keeps the business safe" />

<p class="lede">
    The system was built with the same security practices used by professional business software,
    so management can be confident that staff access is controlled, data is protected, and every
    action is accounted for.
</p>

<h3 class="group-title">Staff Accounts &amp; Login</h3>
<ul>
    <li>Every staff member signs in with their own account; passwords are stored using industry-standard, one-way encryption &mdash; not even a system administrator can see anyone's actual password</li>
    <li>Repeated failed login attempts are automatically throttled, blocking password-guessing attacks</li>
    <li>Login sessions are refreshed on every sign-in and fully cleared on sign-out</li>
    <li>Suspended staff accounts are immediately blocked from signing in</li>
</ul>

<h3 class="group-title">Role-Based Access Control</h3>
<ul>
    <li>Every screen and action is gated behind a specific permission</li>
    <li>Staff only ever see the parts of the system they have been explicitly granted access to</li>
    <li>Access can be changed instantly by the CEO at any time</li>
</ul>

<h3 class="group-title">Full Audit Trail</h3>
<ul>
    <li>Every significant action in the system is permanently logged: who did it, when, and exactly what changed</li>
    <li>Sensitive fields, such as passwords, are automatically hidden from audit records</li>
    <li>Nothing can be quietly changed or deleted without a trace</li>
</ul>

<h3 class="group-title">Protection Against Common Web Attacks</h3>
<ul>
    <li>Cross-Site Request Forgery (CSRF) protection on every form submission</li>
    <li>Cross-Site Scripting (XSS) protection &mdash; all data shown back to users is automatically sanitised</li>
    <li>SQL injection protection &mdash; all database queries are safely parameterised, never built directly from user input</li>
</ul>

<h3 class="group-title">Safe File &amp; Spreadsheet Handling</h3>
<ul>
    <li>Uploaded files (product photos, profile photos, import spreadsheets) are checked for their real file type, not just the file name &mdash; disguised or mismatched files are rejected</li>
    <li>File size limits prevent oversized or abusive uploads</li>
    <li>Uploaded spreadsheets are protected against formula injection, a common spreadsheet-based attack</li>
    <li>Imported data is only ever saved after being previewed and confirmed; a completed import can be rolled back if needed</li>
</ul>

<div class="callout">
    In short: nothing entering the system through an upload or import can execute code, corrupt
    data, or bypass validation &mdash; every file is checked before it is trusted.
</div>

<x-print-footer />

<div class="divider"></div>

<x-print-header title="Security &amp; Data Protection" subtitle="Reliability &amp; ongoing safeguards" />

<h3 class="group-title">Abuse &amp; Overload Protection</h3>
<ul>
    <li>System-wide rate limiting protects against automated abuse</li>
    <li>A stricter limit applies specifically to password-reset requests</li>
</ul>

<h3 class="group-title">Automated Backups</h3>
<ul>
    <li>The full database is automatically backed up every day without any manual step</li>
    <li>Older backups are cleaned up on a rolling schedule, keeping storage under control while preserving recent history</li>
</ul>

<h3 class="group-title">Error Monitoring</h3>
<ul>
    <li>If something unexpected goes wrong on the live system, management is automatically notified by email so it can be investigated right away</li>
    <li>Repeated notifications for the same issue are automatically throttled, so the business is informed without being flooded</li>
</ul>

<h3 class="group-title">Log Management</h3>
<ul>
    <li>System activity logs rotate automatically and do not grow without limit</li>
</ul>

<h3 class="group-title">Quality Assurance</h3>
<ul>
    <li>Every change made to the system is checked against a full suite of automated tests before it is considered complete</li>
    <li>This catches problems before they ever reach staff using the system day to day</li>
</ul>

<div class="callout">
    Together, these safeguards mean the system is not only easy for staff to use, but resilient,
    recoverable, and accountable &mdash; giving management full confidence and oversight over the
    business's data.
</div>

<x-print-footer />

</body>
</html>
