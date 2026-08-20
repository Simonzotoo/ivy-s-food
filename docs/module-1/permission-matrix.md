# Permission Matrix

| Permission | Owner / Super Admin | Manager | Cashier / Sales Officer | Inventory Officer | Production Officer | Purchasing Officer | Accounts Officer | Marketing Officer | Auditor |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| View users | Yes | Yes | No | No | No | No | No | No | No |
| Create users | Yes | No | No | No | No | No | No | No | No |
| Edit users | Yes | No | No | No | No | No | No | No | No |
| Activate or suspend users | Yes | No | No | No | No | No | No | No | No |
| Assign roles | Yes | No | No | No | No | No | No | No | No |
| View roles and permissions | Yes | Yes | No | No | No | No | No | No | No |
| Manage roles and permissions | Yes | No | No | No | No | No | No | No | No |
| View company settings | Yes | Yes | No | No | No | No | No | No | Yes |
| Edit company settings | Yes | No | No | No | No | No | No | No | No |
| View audit logs | Yes | Yes | No | No | No | No | No | No | Yes |
| View product catalogue | Yes | Yes | Yes | Yes | Yes | Yes | No | Yes | No |
| Create product catalogue records | Yes | No | No | Yes | No | No | No | No | No |
| Edit product catalogue records | Yes | No | No | Yes | No | No | No | No | No |
| Activate or deactivate product variants | Yes | No | No | Yes | No | No | No | No | No |
| Export product catalogue | Yes | Yes | No | Yes | No | No | No | Yes | No |

Owner / Super Admin is seeded with unrestricted access. Other role permissions are intentionally conservative and configurable.
