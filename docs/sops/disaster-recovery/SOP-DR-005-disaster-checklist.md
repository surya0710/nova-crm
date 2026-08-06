# SOP-DR-005 — Disaster Checklist

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-DR-005 |
| **Title** | Disaster Checklist |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Disaster Recovery |
| **Owner** | Incident Commander |
| **Reviewer** | Operations Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Provide a single orchestrating checklist for major disasters spanning DB, storage, server, and DNS.

## Scope

- **In scope:** End-to-end DR coordination, communications, and exit criteria.
- **Out of scope:** Routine incidents without multi-system failure.

## Preconditions

- [ ] Disaster declared by Ops Lead / Incident Commander

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| All production systems | Incident Commander | Orchestrate |

## Step-by-step Procedure

### 1. Orchestrate

- [ ] Declare incident commander and scribe
- [ ] Customer/status communication owner assigned (SOP-SUP-005)
- [ ] Execute SOP-DR-001 / 002 / 003 / 004 as applicable
- [ ] Preserve forensic evidence if security-related (SOP-SEC-004)
- [ ] Validate full smoke + monitoring
- [ ] Schedule post-incident review within 5 business days

## Validation Checklist

- [ ] Commander named
- [ ] Applicable DR SOPs executed
- [ ] Service restored or ETA communicated
- [ ] PIR scheduled
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Re-enter checklist from last failed gate; do not skip validation.

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
| **Previous SOP** | [SOP-DR-004 — DNS Recovery](SOP-DR-004-dns-recovery.md) |
| **Next SOP** | [SOP-SUP-002 — Incident Response](../support/SOP-SUP-002-incident-response.md) |
| **Related SOPs** | [SOP-DR-001](SOP-DR-001-database-recovery.md), [SOP-DR-002](SOP-DR-002-storage-recovery.md), [SOP-DR-003](SOP-DR-003-server-recovery.md), [SOP-SEC-004](../security/SOP-SEC-004-security-incident.md) |
| **Related Documents** | [Incident Response Plan](../../operations/incident-response-plan.md) |
| **Required Forms** | Disaster declaration record |
| **Required Checklists** | Disaster orchestration checklist (inline) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
