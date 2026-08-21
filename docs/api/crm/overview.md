# CRM API Reference

Commercial CRM APIs are served at `/api/v1` (Sanctum + `X-Organization-Id`). See [CRM API Overview](../../crm/api/overview.md) for products, contacts, tickets, opportunities, sales activities, forecasting, quotations, and invoices.

## Authentication
Bearer token (`api.access`) plus the entity permission (`products.*`, `quotations.*`, `invoices.*`, `customers.*`).

## Error Codes
- `401` Unauthorized
- `403` Forbidden
- `404` Not Found
- `422` Validation Error
