# Authentication

Authentication model extracted from `routes/auth.php`, `routes/api_identity.php`, `config/sanctum.php`, and related controllers.

---

## Overview

Konnect Nex uses **Laravel Sanctum** for API authentication. There is **no REST API for login, logout, token refresh, password reset, change password, or user profile** as of the current implementation.

| Capability | API | Web |
|------------|-----|-----|
| Login | **Not available** | `POST /login` |
| Logout | **Not available** | `POST /logout` |
| Token refresh | **Not available** | N/A |
| Forgot password | **Not available** | `POST /forgot-password` |
| Reset password | **Not available** | `POST /reset-password` |
| Change password | **Not available** | `PUT /password` |
| Profile | **Not available** | `GET/PATCH /profile` |
| Create API token | **Not available** | `POST /api-tokens` (permission: `api.tokens`) |
| Invitation activation | `POST /api/v1/invitations/activate` | `POST /invitations/{token}` |

### Mobile Authentication Flow (Current)

1. User logs into Konnect Nex web application
2. Navigates to Settings → API Tokens (`/api-tokens`)
3. Creates a token (requires `api.tokens` permission)
4. Copies plaintext token into mobile app (shown once via session flash)
5. Mobile stores token and sends `Authorization: Bearer {token}`

---

## API Token Requirements

```http
Authorization: Bearer {sanctum_personal_access_token}
Accept: application/json
Content-Type: application/json
X-Organization-Id: {organization_id}
```

Token creation (`ApiTokenController`):

```php
$user->createToken($name, ['*']); // abilities default to ['*']
```

Sanctum config (`config/sanctum.php`):

| Setting | Value |
|---------|-------|
| `expiration` | `null` (no automatic expiry) |
| `guard` | `['web']` |
| Stateful domains | localhost, app URL (for SPA cookie auth) |

---

## Public Endpoint: Invitation Activation

### `POST /api/v1/invitations/activate`

| Property | Value |
|----------|-------|
| **URL** | `/api/v1/invitations/activate` |
| **Method** | POST |
| **Authentication** | Not required |
| **Middleware** | `throttle:10,1` |
| **Permission** | None |

#### Request Body

| Field | Rules |
|-------|-------|
| `token` | required, string |
| `password` | required, confirmed, `Password::defaults()` |
| `password_confirmation` | required with password |

#### Success Response `200`

```json
{
  "message": "Account activated.",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "account_status": "active"
  }
}
```

#### Error Responses

| Status | Condition |
|--------|-----------|
| 422 | Invalid or expired token; password validation failed |

#### Example

```http
POST /api/v1/invitations/activate HTTP/1.1
Content-Type: application/json

{
  "token": "invitation-token-from-email",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!"
}
```

---

## Identity API (Admin)

Base: `/api/v1/identity`  
Middleware: `auth:sanctum`, `throttle:api`, `set.organization`, `ensure.organization`, `organization.api`

These endpoints manage user accounts for employees — **not employee self-service**.

### `POST /api/v1/identity/employees/{employee}/login-account`

| Property | Value |
|----------|-------|
| **Permission** | `hrms.manage` (middleware) + `EmployeePolicy` via `CreateEmployeeLoginAccountRequest` |
| **Module** | `hrms` |

#### Request Body (`CreateEmployeeLoginAccountRequest`)

| Field | Rules |
|-------|-------|
| `name` | nullable, string, max:255 |
| `email` | nullable, email, max:255, unique:users |
| `role` | nullable, string, in RBAC role keys |
| `send_invitation` | sometimes, boolean (default true) |
| `portal_access` | sometimes, boolean (default true) |

#### Success `201`

```json
{
  "message": "Login account created.",
  "employee": { },
  "invitation": { }
}
```

#### Error `422`

Employee already has a login account.

---

### `POST /api/v1/identity/users/{user}/invitations`

| Permission | `users.manage` OR `hrms.manage` |

#### Request Body

None required.

#### Success `200`

```json
{
  "message": "Invitation sent.",
  "invitation_id": 123,
  "expires_at": "2026-07-30T12:00:00+00:00"
}
```

Invitation expiry: `config('identity.invitation_expiry_hours')` default **72 hours**.

---

### `GET /api/v1/identity/users/{user}/invitation-status`

| Permission | `users.view` OR `hrms.view` |

#### Success `200`

```json
{
  "account_status": "pending_invitation",
  "display_status": "...",
  "display_label": "...",
  "invitation": {
    "id": 1,
    "sent_at": "...",
    "expires_at": "...",
    "accepted_at": null,
    "revoked_at": null,
    "is_pending": true,
    "is_expired": false,
    "is_acceptable": true
  }
}
```

---

### `POST /api/v1/identity/users/{user}/portal/enable`

| Permission | `users.manage` OR `hrms.manage` |

#### Success `200`

```json
{
  "message": "Portal access enabled.",
  "portal_access_enabled": true
}
```

---

### `POST /api/v1/identity/users/{user}/portal/disable`

| Permission | `users.manage` OR `hrms.manage` |

#### Success `200`

```json
{
  "message": "Portal access disabled.",
  "portal_access_enabled": false
}
```

---

### `POST /api/v1/identity/users/{user}/password-reset`

| Permission | `users.manage` OR `hrms.manage` |

Sends Laravel password reset email. Not a direct password change.

#### Success `200`

```json
{
  "message": "Password reset link sent."
}
```

#### Errors

| Status | Condition |
|--------|-----------|
| 404 | User not in organization |
| 422 | User account disabled |

---

## Web Session Authentication (Reference)

For completeness — used by browser, not mobile API.

### Login — `POST /login`

**FormRequest:** `LoginRequest`

| Field | Rules |
|-------|-------|
| `email` | required, email |
| `password` | required |

Rate limit: 5 attempts. Calls `UserAccountService::assertCanLogin()`.

Failed login lock: `config('identity.failed_login_lock_threshold')` default **10** attempts.

### Logout — `POST /logout`

Middleware: `auth`. Destroys session.

### Forgot Password — `POST /forgot-password`

| Field | Rules |
|-------|-------|
| `email` | required, email |

Token expiry: **60 minutes** (`config/auth.php`).

### Reset Password — `POST /reset-password`

| Field | Rules |
|-------|-------|
| `token` | required |
| `email` | required, email |
| `password` | required, confirmed, `Password::defaults()` |

### Change Password — `PUT /password`

| Field | Rules |
|-------|-------|
| `current_password` | required, current_password |
| `password` | required, confirmed, `Password::defaults()` |

### Profile — `GET/PATCH /profile`

**FormRequest:** `ProfileUpdateRequest`

| Field | Rules |
|-------|-------|
| `name` | required, string, max:255 |
| `email` | required, email, unique (ignore self) |

---

## RBAC Authorization Lookup

Useful for mobile to cache permissions.

### `GET /api/v1/rbac/authorization`

| Permission | `rbac.view` |

#### Query Parameters

| Param | Description |
|-------|-------------|
| `permission` | Optional — check single permission |

#### Response (list)

```json
{
  "permissions": ["ess.access", "dashboard.view", "leave.view"]
}
```

#### Response (single check)

```json
{
  "permission": "ess.access",
  "allowed": true
}
```

---

## Account Lockout & Security

From `config/identity.php`:

| Setting | Default |
|---------|---------|
| `invitation_expiry_hours` | 72 |
| `failed_login_lock_threshold` | 10 |

Org security setting `api_token_expiry_days` (default 365) is stored but **not enforced** when creating Sanctum tokens.

---

## Future Mobile Auth (Not Implemented)

Recommended backend additions:

```
POST /api/v1/auth/login       → { token, user, organizations[] }
POST /api/v1/auth/logout      → revoke current token
POST /api/v1/auth/refresh     → rotate token (if expiry enabled)
GET  /api/v1/auth/me          → current user + employee link status
POST /api/v1/auth/forgot-password
POST /api/v1/auth/reset-password
PUT  /api/v1/auth/password    → change password (authenticated)
```
