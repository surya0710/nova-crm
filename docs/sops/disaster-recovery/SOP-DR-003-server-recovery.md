# SOP-DR-003 — Server Recovery

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-DR-003 |
| **Title** | Server Recovery |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Disaster Recovery |
| **Owner** | DevOps |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Rebuild or replace failed application/infrastructure servers.

## Scope

- **In scope:** Reprovision host, redeploy application, reattach data volumes/backups.
- **Out of scope:** DNS cutover details (SOP-DR-004).

## Preconditions

- [ ] Host declared failed
- [ ] Infrastructure access available

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Cloud / host panel | DevOps | Rebuild |

## Step-by-step Procedure

### 1. Rebuild

1. Provision replacement per SOP-DEP-001.
2. Restore/configure env (SOP-DEP-003), SSL, storage, workers, scheduler.
3. Deploy last known good release; validate.

## Validation Checklist

- [ ] Replacement host serving traffic
- [ ] Deps healthy
- [ ] Monitoring green
- [ ] Old host decommissioned safely
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Keep failed host for forensics if security-related; fail over to alternate region if defined.

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
| **Previous SOP** | [SOP-DR-002 — Storage Recovery](SOP-DR-002-storage-recovery.md) |
| **Next SOP** | [SOP-DR-004 — DNS Recovery](SOP-DR-004-dns-recovery.md) |
| **Related SOPs** | [SOP-DEP-001](../deployment/SOP-DEP-001-server-provisioning.md), [SOP-DEP-002](../deployment/SOP-DEP-002-production-deployment.md) |
| **Related Documents** | [Infrastructure checklist](../../operations/infrastructure-checklist.md) |
| **Required Forms** | Server DR ticket |
| **Required Checklists** | Server rebuild checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
