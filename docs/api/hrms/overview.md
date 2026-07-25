# HRMS API Reference

## Authentication

Bearer API token + optional `X-Organization-Id` header. See [API Overview](../overview.md) for Organization ID guidance.

## Organization ID

Obtain from:

- Organization Settings → Profile / Billing
- Any authenticated API response that includes `organization_id`

## Employees

Operational employee CRUD is primarily web-based. Provisioning must use `App\Services\Hrms\EmployeeProvisioningService` so User, Employee, membership, and role stay synchronized.

### Conceptual create payload

```json
{
  "first_name": "Ada",
  "last_name": "Lovelace",
  "employment_type": "full_time",
  "status": "active",
  "create_user": true,
  "email": "ada@example.com",
  "role": "employee"
}
```

## Error Codes

- `401` Unauthorized
- `403` Forbidden (permission denied — not used for “no employee linked” empty states in ESS)
- `404` Not Found
- `422` Validation Error

## Related

- Organization Settings Guide
- Employee Synchronization (Developer Guide)
