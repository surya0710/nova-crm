# SOP-DEP-008 — SSL

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-DEP-008 |
| **Title** | SSL |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Deployment |
| **Owner** | DevOps / Platform Engineer |
| **Reviewer** | Security Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Terminate TLS correctly so all public traffic is HTTPS with secure cookies.

## Scope

- **In scope:** Certificate install/renewal at reverse proxy and application HTTPS assumptions.
- **Out of scope:** Domain DNS changes (SOP-DEP-009).

## Preconditions

- [ ] Domain available
- [ ] Certificate provider access
- [ ] Reverse proxy configured

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Reverse proxy / load balancer | DevOps | Install cert |

## Step-by-step Procedure

### 1. Terminate TLS

1. Install or renew certificate on reverse proxy.
2. Redirect HTTP → HTTPS.
3. Set `APP_URL` to `https://…` and `SESSION_SECURE_COOKIE=true`.

### 2. Verify

1. Browser padlock valid; no mixed content on login.
2. Certificate expiry recorded for renewal calendar.

## Validation Checklist

- [ ] HTTPS enforced
- [ ] Valid certificate
- [ ] Secure cookies enabled
- [ ] Expiry tracked
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Revert to previous certificate if broken; use HTTP temporary only with Security Lead approval (emergency); notify customers if downtime.

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
| **Previous SOP** | [SOP-DEP-007 — Storage](SOP-DEP-007-storage.md) |
| **Next SOP** | [SOP-DEP-009 — Domain Configuration](SOP-DEP-009-domain-configuration.md) |
| **Related SOPs** | [SOP-DR-004](../disaster-recovery/SOP-DR-004-dns-recovery.md), [SOP-SEC-003](../security/SOP-SEC-003-credential-rotation.md) |
| **Related Documents** | [SSL and storage](../../deployment/ssl-and-storage.md) |
| **Required Forms** | Certificate change ticket |
| **Required Checklists** | TLS verification checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
