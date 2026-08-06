# Employee Login & Workspace

## Overview

Employees can optionally receive a Konnect Nex login. Authentication uses email + password. After activation, employees use the **Employee Workspace** (ESS / My HR) at `/ess` — not a separate portal.

Administrators never create or share passwords. Users set their own password via invitation.

## Create login when hiring

1. Open **HR → Employees → Add Employee**.
2. Check **Create Login Account**.
3. Enter work email, role, and optionally:
   - Send Invitation
   - Portal Access (Employee Workspace)
4. Save. The employee receives an invitation email.

## Convert an existing employee

1. Open the employee profile.
2. Under **Login & portal access**, choose **Create Login Account**.
3. Confirm email/role and send invitation.

## Bulk generate login accounts

1. Open **Employees** list.
2. Select employees without logins.
3. Choose default role and **Generate Login Accounts**.
4. Large batches are queued; existing users are skipped.

## Invitation acceptance

1. Employee opens the invitation link.
2. Sets and confirms a strong password.
3. Signs in at the login page.
4. Opens **My HR** / Employee Workspace when portal access is enabled.

## Portal administration (employee profile)

| Action | Effect |
|--------|--------|
| Resend Invitation | New token; previous pending link invalidated |
| Enable / Disable Portal Access | Toggles Employee Workspace access flag |
| Send Password Reset | Laravel reset email (user sets new password) |
| Lock / Unlock Account | Blocks or restores login |

## Account status on the list

- No login
- Pending Invitation
- Invitation Expired
- Active
- Disabled
- Locked

## Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| Cannot sign in after invite | Invitation not accepted | Open invite link and set password |
| Invitation link fails | Expired or already used | Resend invitation |
| Account locked | Too many failed attempts or admin lock | Unlock from employee profile |
| No Employee Workspace menu | Portal disabled or missing `ess.access` | Enable portal; check role permissions |
| Invite email not received | Org SMTP not configured | Configure Organization Settings → Email; resend |

## Related

- Developer: [Employee provisioning](../../developer/employee-provisioning.md)
- SOP: [SOP-ONB-005 User Provisioning](../../sops/onboarding/SOP-ONB-005-user-provisioning.md)
