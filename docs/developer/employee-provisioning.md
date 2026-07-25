# Employee Identity & Provisioning

Release **1.1.1** establishes a unified Identity & Access Platform on top of the existing Employee ↔ User link.

## Architecture decisions (ADR-001)

1. **Employee Workspace** = existing ESS (`/hrms/ess`, permission `ess.access`). No parallel `/portal`.
2. **All new tenant users** (employees and Team members) use **Invitation → Set Password**. Administrators never assign passwords. Platform operators remain separate.

## Model

```
Employee (HR identity, optional login)
    └── user_id → User (auth identity)
            └── organization membership + role
            └── invitation / account lifecycle
            └── ESS when portal_access_enabled + ess.access
```

Invariants:

- Employees may exist without users.
- One user may link to at most one employee per organization (`employees` unique on `organization_id, user_id`).
- Passwords are never set by administrators for new users.

## Entry points

| Entry | Method / route |
|-------|----------------|
| HRMS create with login | `EmployeeController@store` → `EmployeeProvisioningService::provision()` |
| Convert existing employee | Employee show → Create Login Account → `provisionUserForEmployee()` |
| Bulk generate logins | Employee list → Generate Login Accounts → `BulkEmployeeUserProvisioningService` |
| Team invite | `OrganizationMemberService::addMember()` → invitation |
| API | `POST /api/v1/identity/employees/{employee}/login-account` |

## Invitation lifecycle

1. Create user with `account_status = pending_invitation` and unusable password hash.
2. Store hashed token in `user_invitations` (expiry from `config/identity.php`).
3. Email org-branded invitation (`UserInvitationMail`).
4. Guest opens `/invitations/{token}`, sets password.
5. Account becomes `active`; welcome email/notification sent.

Resend invalidates prior pending tokens. Expired invitations are re-sendable.

## Account statuses

| Status | Meaning |
|--------|---------|
| `pending_invitation` | Invited; cannot login until accept |
| `active` | Can authenticate |
| `disabled` | Admin disabled |
| `locked` | Locked (manual or failed-attempt threshold) |
| *Invitation Expired* | Derived display when pending + token expired |

## Services

- `App\Services\Identity\UserInvitationService`
- `App\Services\Identity\UserAccountService`
- `App\Services\Identity\BulkEmployeeUserProvisioningService`
- `App\Services\Hrms\EmployeeProvisioningService` (orchestrates hire + invite)

## Security

- CSRF on web forms; Sanctum + RBAC on APIs
- Tokens stored hashed; one-time use; expiry
- Login throttling (Breeze) + account status gates
- Failed login attempts / optional auto-lock (`identity.failed_login_lock_threshold`)
- Org-scoped invitations and audit events

## Related docs

- [Employee login workflow](../hrms/user-guide/employee-login.md)
- [SOP user provisioning](../sops/onboarding/SOP-ONB-005-user-provisioning.md)
