# Assumptions And Decisions

- The requirements document dated 18 August 2026 is treated as the current source baseline.
- Unknown company address, phone, email, website and tax details are intentionally blank.
- Ghana cedi is stored as `GHS`; time zone is `Africa/Accra`.
- The first production deployment target is Hostinger Business shared hosting.
- The architecture is a modular monolith, not microservices.
- Queues use the database driver and must not require a permanently running worker.
- Local and automated tests use SQLite; production uses MariaDB/MySQL.
- Development owner seeding is controlled by `.env` values and does not commit a production password.
- Owner / Super Admin access is represented by role metadata and backed by granular permissions.
- Audit values are recorded through `AuditService` so sensitive keys can be redacted consistently.
- Users are soft deleted to protect future audit and business-record relationships.
- Purchase attachments initially store safe document references only; binary upload handling will be reviewed under file-upload security.
- Supplier balances and purchase settlement belong to later financial modules and are not invented in Module 3.
- Module 4 records finished production output, but sellable finished-product stock remains deferred to the Module 5 inventory ledger.
- Products cannot be completed in production without an approved recipe; unknown recipes stay configurable and unapproved.
