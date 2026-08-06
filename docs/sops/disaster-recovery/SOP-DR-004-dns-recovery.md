# SOP-DR-004 — DNS Recovery

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-DR-004 |
| **Title** | DNS Recovery |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Disaster Recovery |
| **Owner** | DevOps |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Restore correct DNS resolution during outage or hijack/misconfiguration.

## Scope

- **In scope:** DNS record correction, TTL handling, and verification.
- **Out of scope:** Certificate reissue (SOP-DEP-008) when hostname changes.

## Preconditions

- [ ] DNS failure or mis-point confirmed
- [ ] DNS provider access

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| DNS provider | DevOps | Fix records |

## Step-by-step Procedure

### 1. Repair DNS

1. Set records to known-good targets.
2. Lower TTL if upcoming changes expected.
3. Verify propagation; smoke HTTPS endpoints.

## Validation Checklist

- [ ] DNS resolves correctly from multiple resolvers
- [ ] HTTPS OK
- [ ] Customers updated if needed
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Point to status/maintenance page if origin still down; escalate registrar lock if hijack suspected (SOP-SEC-004).

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
| **Previous SOP** | [SOP-DR-003 — Server Recovery](SOP-DR-003-server-recovery.md) |
| **Next SOP** | [SOP-DR-005 — Disaster Checklist](SOP-DR-005-disaster-checklist.md) |
| **Related SOPs** | [SOP-DEP-009](../deployment/SOP-DEP-009-domain-configuration.md), [SOP-DEP-008](../deployment/SOP-DEP-008-ssl.md) |
| **Related Documents** | [Deployment guide](../../deployment/guide.md) |
| **Required Forms** | DNS DR ticket |
| **Required Checklists** | DNS verification checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
