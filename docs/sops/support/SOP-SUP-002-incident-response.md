# SOP-SUP-002 — Incident Response

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-SUP-002 |
| **Title** | Incident Response |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Support |
| **Owner** | On-call / Support Lead |
| **Reviewer** | Operations Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Coordinate detection, mitigation, communication, and recovery for production incidents.

## Scope

- **In scope:** P1/P2 incident declaration, war-room coordination, mitigation, and handoff to RCA.
- **Out of scope:** Routine ticket handling and non-incident bugs.

## Preconditions

- [ ] Incident suspected or declared
- [ ] [Incident Response Plan](../../operations/incident-response-plan.md) available

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Monitoring / logs | On-call | Diagnose |
| Status / customer comms | Support Lead | Updates |

## Step-by-step Procedure

### 1. Declare and contain

1. Follow [Incident Response Plan](../../operations/incident-response-plan.md).
2. Assign incident commander; open bridge/channel.
3. Mitigate customer impact (failover, disable feature flag, scale, rollback per SOP-REL-004).

### 2. Communicate and close

1. P1 updates at least every 60 minutes until mitigated (SOP-SUP-005).
2. Confirm mitigation; schedule RCA within 5 business days for P1/P2.

## Validation Checklist

- [ ] Incident commander named
- [ ] Mitigation recorded
- [ ] Customer updates sent on cadence
- [ ] RCA scheduled for P1/P2
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

If mitigation worsens impact, reverse last change; restore from backup only with Tech Lead approval (SOP-MNT-003 / SOP-DR-*).

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
| **Previous SOP** | [SOP-SUP-001 — Ticket Handling](SOP-SUP-001-ticket-handling.md) |
| **Next SOP** | [SOP-SUP-003 — Bug Escalation](SOP-SUP-003-bug-escalation.md) |
| **Related SOPs** | [SOP-REL-004](../release-management/SOP-REL-004-rollback.md), [SOP-SEC-004](../security/SOP-SEC-004-security-incident.md), [SOP-DR-005](../disaster-recovery/SOP-DR-005-disaster-checklist.md) |
| **Related Documents** | [Incident Response Plan](../../operations/incident-response-plan.md) |
| **Required Forms** | Incident record |
| **Required Checklists** | Incident commander checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
