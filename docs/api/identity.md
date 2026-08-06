# Identity API (v1)

Base path: `/api/v1`

## Activate invitation (public)

`POST /api/v1/invitations/activate`

```json
{ "token": "...", "password": "...", "password_confirmation": "..." }
```

## Authenticated (Sanctum + org header/session)

| Method | Path | Permission |
|--------|------|------------|
| POST | `/identity/employees/{employee}/login-account` | `hrms.manage` |
| POST | `/identity/users/{user}/invitations` | `users.manage` or `hrms.manage` |
| GET | `/identity/users/{user}/invitation-status` | `users.view` or `hrms.view` |
| POST | `/identity/users/{user}/portal/enable` | `users.manage` or `hrms.manage` |
| POST | `/identity/users/{user}/portal/disable` | `users.manage` or `hrms.manage` |
| POST | `/identity/users/{user}/password-reset` | `users.manage` or `hrms.manage` |

All mutating actions write audit log events.
