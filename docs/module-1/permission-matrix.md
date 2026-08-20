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
| View materials | Yes | Yes | No | Yes | Yes | Yes | No | No | Yes |
| Manage materials | Yes | No | No | Yes | No | Yes | No | No | No |
| View suppliers | Yes | Yes | No | Yes | No | Yes | No | No | Yes |
| Manage suppliers | Yes | No | No | No | No | Yes | No | No | No |
| View purchases | Yes | Yes | No | Yes | No | Yes | No | No | Yes |
| Create purchases | Yes | No | No | No | No | Yes | No | No | No |
| Edit purchases | Yes | No | No | No | No | Yes | No | No | No |
| Receive purchases | Yes | No | No | Yes | No | Yes | No | No | No |
| Return purchases | Yes | No | No | Yes | No | Yes | No | No | No |
| View material stock movements | Yes | Yes | No | Yes | Yes | Yes | No | No | Yes |
| View recipes | Yes | Yes | No | Yes | Yes | No | No | No | Yes |
| Manage recipes | Yes | No | No | No | Yes | No | No | No | No |
| Approve recipes | Yes | Yes | No | No | No | No | No | No | No |
| View production | Yes | Yes | No | Yes | Yes | No | No | No | Yes |
| Plan production | Yes | No | No | No | Yes | No | No | No | No |
| Create production batches | Yes | No | No | No | Yes | No | No | No | No |
| Complete production | Yes | No | No | No | Yes | No | No | No | No |
| Reverse production | Yes | No | No | No | No | No | No | No | No |
| View inventory | Yes | Yes | No | Yes | Yes | No | No | No | Yes |
| Adjust stock | Yes | No | No | Yes | No | No | No | No | No |
| Create stocktakes | Yes | No | No | Yes | No | No | No | No | No |
| Approve stocktake variances | Yes | Yes | No | No | No | No | No | No | No |
| Post production output to inventory | Yes | No | No | Yes | No | No | No | No | No |

Owner / Super Admin is seeded with unrestricted access. Other role permissions are intentionally conservative and configurable.
