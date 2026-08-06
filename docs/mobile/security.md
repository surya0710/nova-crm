# Security

Security model for Konnect Nex HRMS mobile integration.

---

## Transport Security

| Requirement | Implementation |
|-------------|----------------|
| HTTPS | Required in production — all API traffic over TLS |
| Certificate pinning | Recommended for mobile apps (client-side) |
| HSTS | Configure at reverse proxy level |

---

## Authentication

### Sanctum Personal Access Tokens

- Tokens issued via web UI (`POST /api-tokens`)
- Sent as `Authorization: Bearer {token}`
- Default abilities: `['*']` (full access for token owner)
- No refresh token flow — revoke and reissue on compromise
- `config/sanctum.php` expiration: `null` (no auto-expiry)

### Token Storage (Mobile)

- Store in **secure enclave** (Keychain / Keystore via `flutter_secure_storage`)
- Never log tokens
- Clear on logout
- Do not include in crash reports

### Account Lockout

- Failed login threshold: 10 attempts (`config/identity.php`)
- Web login rate limit: 5 attempts per email+IP

---

## Organization Isolation

| Control | Mechanism |
|---------|-----------|
| Tenant binding | `X-Organization-Id` → `TenantContext` |
| Query scoping | All services filter by `organization_id` |
| Cross-org access | Returns 404, not 403 |
| Suspended orgs | API blocked via `organization.api` middleware |
| Archived orgs | API blocked; web mutations blocked |

---

## Dynamic RBAC

- ~300 granular permissions in `config/rbac.php`
- Evaluated per request via `AuthorizationService`
- 300-second permission cache per user/org
- Organization owners bypass permission checks
- Mobile should cache permissions and refresh on org switch

---

## Module Licensing

- Prevents access to unlicensed features (HRMS, projects, etc.)
- Checked in widget providers, lookup service, web `module` middleware
- 403 response does not leak license details

---

## Audit Logging

Identity API mutations write audit events:

- Login account creation
- Invitation sent
- Portal enable/disable
- Password reset initiated

HRMS sensitive changes (employee updates, leave approvals) audited via web services.

Mobile API clients should expect actions to be audited when APIs are added.

---

## Data Protection

| Data type | Guidance |
|-----------|----------|
| PII (employee profile) | Minimize local cache; encrypt at rest |
| Documents | Stream via authenticated download only |
| Payslips | Not yet available via API |
| Tokens | Secure storage only |

---

## API Rate Limiting

| Limiter | Limit |
|---------|-------|
| `api` | 120 requests/minute per token or IP |
| Identity activate | 10/minute per IP |

Implement client-side backoff on 429.

---

## Input Validation

- All mutations validated via Form Requests
- Business rules enforced in services (attendance, leave)
- SQL injection prevented via Eloquent ORM
- XSS not applicable to JSON API clients

---

## CORS

Configured for SPA domains in `config/sanctum.php` stateful domains.

Mobile native apps are not subject to CORS (no browser origin).

---

## Password Policy

`Password::defaults()` — Laravel default rules:

- Minimum 8 characters
- Mixed case, numbers (per Laravel version defaults)

Applied to: invitation activation, password reset, change password (web).

---

## Invitation Security

- Token-based activation (`POST /api/v1/invitations/activate`)
- Expiry: 72 hours default (`IDENTITY_INVITATION_EXPIRY_HOURS`)
- Throttled: 10 requests/minute

---

## Org Security Settings

Stored in `organization.settings.security`:

| Setting | Default | Enforced |
|---------|---------|----------|
| `api_token_expiry_days` | 365 | **Not enforced** on Sanctum token creation |

Configurable via Administration → Security UI.

---

## Mobile Security Checklist

- [ ] HTTPS only
- [ ] Secure token storage
- [ ] Send `X-Organization-Id` on every request
- [ ] Validate SSL certificates (or pin in production)
- [ ] Clear sensitive data on logout
- [ ] Do not cache documents locally without encryption
- [ ] Handle 401 by clearing session
- [ ] Implement certificate transparency monitoring (optional)
- [ ] Obfuscate app binary (release builds)
- [ ] Use biometric lock for app access (recommended)

---

## Reporting Security Issues

Follow organization security SOP: `docs/sops/security/`

---

## Offline Security

- Cached attendance data is low sensitivity
- Do not queue check-in/out offline (server validation required)
- Clear cache on logout

---

## Future: Mobile Auth Security

When login API is added, require:

- Short-lived access tokens
- Refresh token rotation
- Device binding
- Optional MFA support
