# SOP-DEP-009 — Domain Configuration

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-DEP-009 |
| **Title** | Domain Configuration |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Deployment |
| **Owner** | DevOps / Platform Engineer |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Configure DNS and application URL bindings for customer or platform domains.

## Scope

- **In scope:** DNS records, APP_URL alignment, and custom domain notes for tenants.
- **Out of scope:** SSL issuance (SOP-DEP-008) and DNS recovery (SOP-DR-004).

## Preconditions

- [ ] Domain ownership verified
- [ ] Target host/IP known

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| DNS provider | DevOps | Create/update records |

## Step-by-step Procedure

### 1. Configure DNS

1. Create A/AAAA/CNAME records as designed.
2. Wait for propagation; verify with dig/nslookup.

### 2. Bind application

1. Update `APP_URL` / tenant domain settings.
2. Complete SSL (SOP-DEP-008).
3. Smoke the hostname login page.

## Validation Checklist

- [ ] DNS resolves to correct target
- [ ] APP_URL matches
- [ ] HTTPS works
- [ ] Smoke login OK
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Revert DNS TTL-friendly prior records; follow SOP-DR-004 if outage; communicate ETA via Support/CS.

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
| **Previous SOP** | [SOP-DEP-008 — SSL](SOP-DEP-008-ssl.md) |
| **Next SOP** | [SOP-MON-001 — Daily Health Check](../monitoring/SOP-MON-001-daily-health-check.md) |
| **Related SOPs** | [SOP-DR-004](../disaster-recovery/SOP-DR-004-dns-recovery.md), [SOP-ONB-002](../onboarding/SOP-ONB-002-organization-provisioning.md) |
| **Related Documents** | [Deployment guide](../../deployment/guide.md) |
| **Required Forms** | Domain change request |
| **Required Checklists** | DNS cutover checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
