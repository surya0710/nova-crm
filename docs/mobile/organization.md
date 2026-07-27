# Organization Context

Multi-tenancy implementation for API clients.

---

## Overview

Every tenant-scoped API request operates within a single **organization** (tenant). The organization is resolved at request time and stored in `TenantContext`.

**Primary key:** `organizations.id`  
**Header:** `X-Organization-Id: {integer}`

---

## Resolution Order

Implemented in `SetCurrentOrganization` middleware:

1. Clear `TenantContext`
2. Session `current_organization_id` (web/SPA)
3. Header `X-Organization-Id` (API/mobile — **preferred for mobile**)
4. Validate ID is in `$user->organizations()`
5. On invalid header → clear session org, **fall back to user's first organization** (does not abort)
6. Super-admins pass through without tenant binding

---

## Required Headers

```http
Authorization: Bearer {token}
X-Organization-Id: 42
Accept: application/json
```

Mobile apps should **always** send `X-Organization-Id` explicitly, especially for users in multiple organizations.

---

## Middleware Stack

Applied to most `/api/v1/*` routes:

| Middleware | Purpose |
|------------|---------|
| `set.organization` | Resolve org → `TenantContext` |
| `ensure.organization` | Require tenant is set |
| `organization.api` | Block suspended/archived org API access |

### `organization.api` Lifecycle Rules

From `OrganizationLifecycleService::assertApiAccess()`:

| Org status | API access |
|------------|------------|
| Active | Allowed |
| Suspended | **403** — "API access is disabled for suspended organizations." |
| Archived | **403** — "API access is disabled for archived organizations." |

Web routes additionally use `organization.lifecycle` middleware which blocks mutations on archived orgs.

---

## Module Licensing

HRMS endpoints require the `hrms` module to be licensed for the organization.

Checked via:

- Dashboard widget `subscription_module: hrms`
- Lookup entities with `license_module: hrms`
- Web routes via `module` middleware

Denied → **403** `"Module not licensed."`

Module resolution: `ModuleSubscriptionService::moduleAllowed()` checks plan + `organization_modules` assignments.

---

## Organization Switching

**Supported:** Client-side by changing `X-Organization-Id` header value.

**Not supported:** Dedicated "switch organization" API endpoint. Mobile should:

1. List user's organizations (from login response when available, or web profile data)
2. Let user select organization
3. Update stored `organization_id` and refresh permissions via `/rbac/authorization`

---

## Unauthorized Scenarios

| Scenario | HTTP | Response |
|----------|------|----------|
| No token | 401 | Unauthenticated |
| Token valid, no org context | 422 or redirect (web) | "Organization context is required." |
| User not member of org | Falls back to first org | Silent fallback — **client should validate** |
| Suspended org | 403 | Lifecycle message |
| Archived org | 403 | Lifecycle message |
| Missing permission | 403 | Forbidden |
| Module not licensed | 403 | "Module not licensed." |

---

## Finding Organization ID

| Source | Location |
|--------|----------|
| Settings UI | Organization Settings → Billing |
| API responses | `organization_id` in many payloads |
| User membership | Organization list (web profile) |

---

## Tenant Isolation Guarantees

- All HRMS services scope queries by `TenantContext::id()`
- Cross-organization resource access returns **404** (not 403) to avoid leaking existence
- Notifications filtered by `data->organization_id`
- Lookup search scoped to current organization

---

## Example Request

```http
GET /api/v1/attendance/dashboard HTTP/1.1
Host: crm.example.com
Authorization: Bearer 1|abcdef...
X-Organization-Id: 42
Accept: application/json
```

---

## Multi-Organization Mobile UX

Recommended flow:

```
App Launch
  → Load stored token + org_id
  → GET /rbac/authorization (verify access)
  → GET /attendance/dashboard
  → On 403 lifecycle error → show org suspended message
  → On empty_state → show "contact HR to link employee record"
```

Organization picker in app settings for users with multiple memberships.
