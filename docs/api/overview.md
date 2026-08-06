# API Documentation — Organization ID & Authentication

## Organization ID

Every tenant-scoped API call runs in the context of an **organization**. The Organization ID is the primary key of the `organizations` table.

### Where to find it

| Location | How |
|----------|-----|
| Organization Profile | Organization Settings → Profile (URL `/organization/settings`) |
| Billing page | Organization Settings → Billing shows `Organization ID` |
| API responses | Many payloads include `organization_id` |
| Team / membership | Organization membership APIs return the current org context |

Example (Billing / Settings UI):

```
Organization ID: 42
```

## Authentication

NovaCRM APIs use Bearer tokens created under **Settings → API Tokens**.

```http
Authorization: Bearer {your_api_token}
Accept: application/json
Content-Type: application/json
X-Organization-Id: 42
```

If your token is scoped to a single organization, the header may be optional. Prefer sending `X-Organization-Id` explicitly for multi-tenant tokens.

## Example — List CRM leads

```http
GET /api/leads HTTP/1.1
Host: your-nova-crm.host
Authorization: Bearer 1|xxxxxxxx
Accept: application/json
X-Organization-Id: 42
```

## Example — Provision employee (service layer / future REST)

Employee creation across UI, API, and import must go through `EmployeeProvisioningService`, which creates:

- User (optional / when requested)
- Employee
- Profile relations
- Organization membership
- Role assignment
- Welcome notification (optional)

## Errors

| Code | Meaning |
|------|---------|
| 401 | Missing or invalid token |
| 403 | Authenticated but lacking permission |
| 404 | Resource not found in current organization |
| 422 | Validation failed |

## Postman

Import `postman/NovaCRM-API.postman_collection.json`. Set collection variables:

- `base_url`
- `api_token`
- `organization_id`
