# SOP-OFF-004 — Account Disable

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-OFF-004 |
| **Title** | Account Disable |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Offboarding |
| **Owner** | Platform Operator |
| **Reviewer** | Operations Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Disable organization and user access at the cancellation effective date.

## Scope

- **In scope:** Org suspend/disable, user lockouts, and API token revocation.
- **Out of scope:** Permanent deletion (SOP-OFF-006).

## Preconditions

- [ ] Export completed or waived in writing
- [ ] Final backup complete (SOP-OFF-003)
- [ ] Effective date reached

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Platform Organizations / Users | Platform Operator | Disable access |

## Step-by-step Procedure

### 1. Disable

1. Suspend/disable organization.
2. Lock users (SOP-SEC-006 patterns).
3. Revoke API tokens/integrations.
4. Confirm login blocked; notify Support.

## Validation Checklist

- [ ] Org disabled
- [ ] Users locked
- [ ] Tokens revoked
- [ ] Support notified
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Re-enable only with Billing/Ops Lead written approval if cancellation reversed.

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
| **Previous SOP** | [SOP-OFF-003 — Backup](SOP-OFF-003-backup.md) |
| **Next SOP** | [SOP-OFF-005 — Data Retention](SOP-OFF-005-data-retention.md) |
| **Related SOPs** | [SOP-ADM-002](../administration/SOP-ADM-002-organization-administration.md), [SOP-SEC-006](../security/SOP-SEC-006-user-lockout.md) |
| **Related Documents** | [Platform Admin Guide](../../onboarding/platform-admin-guide.md) |
| **Required Forms** | Disable approval note |
| **Required Checklists** | Account disable checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
