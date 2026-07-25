# SOP-REL-005 — Post-release Validation

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-REL-005 |
| **Title** | Post-release Validation |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Release Management |
| **Owner** | QA / Ops / Support |
| **Reviewer** | Release Manager |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Validate production after release and watch for regressions during the soak window.

## Scope

- **In scope:** Smoke, monitoring watch (24h), support briefing confirmation, and release closure.
- **Out of scope:** Next release preparation.

## Preconditions

- [ ] Deploy completed or rollback completed

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Production + Monitoring | QA / Ops | Validate |
| Support | Support Lead | Watch tickets |

## Step-by-step Procedure

### 1. Validate

1. Execute smoke ([smoke.md](../../release/smoke.md)).
2. Watch monitoring 24h.
3. Confirm support has release notes / known issues ([Release communication](../../support/release-communication.md)).
4. Close release ticket or open follow-ups.

## Validation Checklist

- [ ] Smoke passed
- [ ] 24h watch completed or still in progress with owner
- [ ] Support briefed
- [ ] Release closed or follow-ups ticketed
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Any P1 during soak triggers SOP-SUP-002 and possible SOP-REL-004.

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
| **Previous SOP** | [SOP-REL-004 — Rollback](SOP-REL-004-rollback.md) |
| **Next SOP** | [SOP-MON-001 — Daily Health Check](../monitoring/SOP-MON-001-daily-health-check.md) |
| **Related SOPs** | [SOP-SUP-005](../support/SOP-SUP-005-customer-communication.md), [SOP-REL-001](SOP-REL-001-release-preparation.md) |
| **Related Documents** | [Smoke](../../release/smoke.md), [Release communication](../../support/release-communication.md) |
| **Required Forms** | Post-release report |
| **Required Checklists** | Soak watch checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
