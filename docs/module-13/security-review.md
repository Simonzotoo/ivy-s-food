# Security Review

Status: Tested

- Authentication uses guarded login, throttling, password reset, CSRF middleware and session regeneration.
- Authorization is permission-based through seeded roles and route middleware; Owner / Super Admin remains unrestricted.
- Direct URL access is protected on all module routes by `can:*` middleware.
- Audit logging redacts sensitive keys and records security-sensitive actions.
- Real SMS/WhatsApp delivery is disabled until credentials and provider approval are supplied.
- Imports use pasted CSV content; broad file-upload handling is intentionally avoided.
- Purchase and expense attachment fields currently store references only.
- Security headers are added through `SecurityHeaders`.
- Production must use HTTPS with `SESSION_SECURE_COOKIE=true`, `APP_DEBUG=false`, and a private `.env`.

Residual risks requiring owner/operator action:

- Final provider credentials must be stored only in `.env`.
- Physical stock and opening credit imports require business reconciliation before production use.
- Formal penetration testing is recommended before live financial use.
