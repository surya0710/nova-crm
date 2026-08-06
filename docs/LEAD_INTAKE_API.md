# Lead Intake API

External systems can submit leads into Konnect Nex using a secure REST API. This is the standard integration layer for landing pages, marketing websites, mobile apps, and third-party services such as MyVisaRoute.

## Base URL

```
POST {APP_URL}/api/v1/leads
```

Example (local development):

```
POST http://localhost/nova-crm/public/api/v1/leads
```

## Authentication

Konnect Nex uses **Laravel Sanctum Personal Access Tokens**.

### Generate a token

1. Sign in to Konnect Nex as a user with the **Manage personal API tokens** permission.
2. Open **Settings → API Tokens**.
3. Create a token and copy the plain-text value immediately (it is shown only once).

### Send credentials with each request

| Header | Value |
|--------|-------|
| `Authorization` | `Bearer {your_token}` |
| `X-Organization-Id` | `{organization_id}` |
| `Content-Type` | `application/json` |
| `Accept` | `application/json` |

The token is tied to a user account. The user must belong to the organization specified in `X-Organization-Id` and hold:

- `api.access` — required for all API routes
- `leads.create` — required to submit leads

Requests without a valid token return **401 Unauthorized**.

## Rate limiting

The intake endpoint allows **60 requests per minute per token**. Exceeding this limit returns **429 Too Many Requests**.

## Request payload

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | Lead full name (max 255) |
| `phone` | string | Yes | Contact phone (max 50) |
| `source` | string | Yes | Lead source identifier (max 50), e.g. `website`, `api`, `myvisaroute` |
| `email` | string | No | Email address |
| `form_type` | string | No | Form identifier, e.g. `contact`, `quote` |
| `source_url` | string | No | URL where the lead was captured |
| `service_interest` | string | No | Service or product interest |
| `message` | string | No | Free-text message (stored as a lead note) |
| `custom_fields` | object | No | Industry-specific key/value pairs (JSON) |
| `assigned_to` | integer | No | User ID to assign within the organization |

### Example request

```http
POST /api/v1/leads HTTP/1.1
Authorization: Bearer 1|abc123...
X-Organization-Id: 42
Content-Type: application/json
Accept: application/json

{
  "name": "Jane Prospect",
  "email": "jane@example.com",
  "phone": "+1 555 0100",
  "source": "myvisaroute",
  "form_type": "visa_inquiry",
  "source_url": "https://myvisaroute.com/contact",
  "service_interest": "student",
  "message": "Interested in studying in Canada.",
  "custom_fields": {
    "visa_type": "student",
    "destination_country": "Canada",
    "travel_month": "2026-09",
    "relationship": "Brother"
  }
}
```

## Normalization

Payloads are normalized before lead creation:

- Strings are trimmed; empty strings become `null`.
- Phone numbers are stripped to digits (optional leading `+`).
- Email addresses are lowercased.
- Known visa/service values are mapped, e.g. `student` → `Student Visa`, `visitor` → `Visitor Visa`.
- `form_type`, `source_url`, and `service_interest` are merged into `custom_fields` for storage.

## Responses

### 201 Created

```json
{
  "success": true,
  "lead_id": 123,
  "message": "Lead created successfully."
}
```

### 401 Unauthorized

Missing or invalid token.

### 403 Forbidden

Authenticated user lacks `api.access` or `leads.create`.

### 409 Conflict — duplicate lead

An open lead with the same email or phone already exists in the organization.

```json
{
  "success": false,
  "lead_id": 98,
  "message": "A lead with this email or phone already exists."
}
```

### 422 Unprocessable Entity

Validation failed. Response includes standard Laravel validation errors.

### 429 Too Many Requests

Rate limit exceeded.

### 500 Internal Server Error

Unexpected failure.

```json
{
  "success": false,
  "message": "An unexpected error occurred while creating the lead."
}
```

## Business rules

1. Leads are always created with status **New**.
2. Leads are scoped to the organization from `X-Organization-Id` (or the user's default organization if omitted).
3. Duplicate detection matches **email or phone** against open leads (excluding converted, won, and lost).
4. `custom_fields` is stored as JSON on the lead — no new database columns per field.
5. `message` is stored as an internal lead note attributed to the API user.
6. Audit events: `created` (automatic) and `received_via_api` (includes source metadata).
7. Users with `leads.manage` are notified when a lead has no assignee; the assignee is notified when `assigned_to` is set.

## cURL example

```bash
curl -X POST "https://your-domain.com/api/v1/leads" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-Organization-Id: 42" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Jane Prospect",
    "email": "jane@example.com",
    "phone": "+15550100",
    "source": "website",
    "custom_fields": {
      "destination_country": "Canada"
    }
  }'
```
