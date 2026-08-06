# SOP-DEP-001 — Server Provisioning

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-DEP-001 |
| **Title** | Server Provisioning |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Deployment |
| **Owner** | DevOps / Platform Engineer |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Provision compute, OS baselines, and host prerequisites required to run Konnect Nex.

## Scope

- **In scope:** Server create/size, OS packages, PHP/Node/MySQL clients, firewall basics, and access accounts.
- **Out of scope:** Application deploy (SOP-DEP-002), SSL certs detail (SOP-DEP-008), and DNS (SOP-DEP-009).

## Preconditions

- [ ] Infrastructure request approved
- [ ] Environment named (staging/production)
- [ ] [Infrastructure checklist](../../operations/infrastructure-checklist.md) available

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Cloud / host panel | DevOps | Create VM / service |
| SSH / RDP vault | DevOps | Store credentials |

## Step-by-step Procedure

### 1. Provision host

1. Create VM or service per sizing notes in [Infrastructure checklist](../../operations/infrastructure-checklist.md).
2. Apply OS updates; install required runtimes (PHP, Composer, Node, database client as designed).
3. Create deploy user; disable password SSH where policy requires keys.

### 2. Baseline

1. Configure firewall to allow only required ports (80/443/SSH as designed).
2. Mount disks; set timezone UTC unless exception approved.
3. Record host inventory on the change ticket.

## Validation Checklist

- [ ] Host reachable via approved access path
- [ ] Runtimes installed at required versions
- [ ] Credentials stored in vault
- [ ] Inventory recorded
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Decommission mis-provisioned host after confirming no customer data; revoke keys; update inventory.

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
| **Previous SOP** | [SOP-REL-001 — Release Preparation](../release-management/SOP-REL-001-release-preparation.md) |
| **Next SOP** | [SOP-DEP-002 — Production Deployment](SOP-DEP-002-production-deployment.md) |
| **Related SOPs** | [SOP-DEP-003](SOP-DEP-003-environment-configuration.md), [SOP-DR-003](../disaster-recovery/SOP-DR-003-server-recovery.md) |
| **Related Documents** | [Infrastructure checklist](../../operations/infrastructure-checklist.md), [Deployment overview](../../deployment/overview.md) |
| **Required Forms** | Infrastructure change request |
| **Required Checklists** | [Infrastructure checklist](../../operations/infrastructure-checklist.md) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
