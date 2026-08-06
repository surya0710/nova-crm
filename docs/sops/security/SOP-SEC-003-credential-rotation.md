# SOP-SEC-003 — Credential Rotation

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-SEC-003 |
| **Title** | Credential Rotation |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Security |
| **Owner** | DevOps / Security |
| **Reviewer** | Security Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Rotate secrets, API keys, and passwords on schedule or after suspected exposure.

## Scope

- **In scope:** Application secrets, DB passwords, API tokens, and shared vault entries.
- **Out of scope:** User password resets initiated by end users without exposure.

## Preconditions

- [ ] Inventory of secrets
- [ ] Maintenance window if service restart required

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Secrets vault / `.env` | DevOps | Rotate and redeploy |

## Step-by-step Procedure

### 1. Rotate

1. Generate new secret in vault.
2. Update runtime config; restart dependent services.
3. Revoke old secret after verification.
4. Record rotation on security ticket.

## Validation Checklist

- [ ] New secret active
- [ ] Old secret revoked
- [ ] Services healthy
- [ ] Ticket evidence complete
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Revert to previous secret only if new secret breaks production and old not yet revoked; otherwise declare incident.

## Exceptions

| Exception | Handling | Approver |
|-----------|----------|----------|
| None documented | Follow change-management exception path | Operations Lead |

## Audit Trail

Record the following for every execution:

| Field | Source |
|-------|--------|
| Date / time (UTC) | Ticket or change record |
| Operator | Authenticated user |
| Organization / environment | Ticket fields |
| Actions taken | Procedure steps completed |
| Evidence links | Attachments / URLs |
| Approval (if required) | Approver name + timestamp |

## Cross References

| Relation | Reference |
|----------|-----------|
| **Previous SOP** | [SOP-SEC-002 — MFA Enforcement](SOP-SEC-002-mfa-enforcement.md) |
| **Next SOP** | [SOP-SEC-004 — Security Incident](SOP-SEC-004-security-incident.md) |
| **Related SOPs** | [SOP-DEP-003](../deployment/SOP-DEP-003-environment-configuration.md) |
| **Related Documents** | [Deployment overview](../../deployment/overview.md) |
| **Required Forms** | Secret rotation ticket |
| **Required Checklists** | Secret inventory checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
