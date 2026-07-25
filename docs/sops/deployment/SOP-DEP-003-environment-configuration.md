# SOP-DEP-003 — Environment Configuration

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-DEP-003 |
| **Title** | Environment Configuration |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Deployment |
| **Owner** | DevOps / Platform Engineer |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Configure and verify production `.env` and runtime settings for secure, correct operation.

## Scope

- **In scope:** Environment variables, debug flags, URL, queue/cache stores, and session security.
- **Out of scope:** SSL termination details (SOP-DEP-008) and domain DNS (SOP-DEP-009).

## Preconditions

- [ ] Host provisioned
- [ ] Secrets available from vault
- [ ] [Deployment overview](../../deployment/overview.md) reviewed

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Production `.env` / secrets manager | DevOps | Edit with change control |

## Step-by-step Procedure

### 1. Set required values

1. Verify `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` https.
2. Confirm database, cache, queue, mail, and filesystem drivers.
3. Confirm `SESSION_SECURE_COOKIE=true` when HTTPS is terminated correctly.

### 2. Apply caches

1. `php artisan config:cache` after changes.
2. Restart PHP-FPM / queue workers as needed.

## Validation Checklist

- [ ] Production debug disabled
- [ ] APP_URL matches public HTTPS URL
- [ ] Config cache rebuilt
- [ ] Smoke login succeeds
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Restore previous `.env` from vault backup; rebuild config cache; restart services; notify on-call if auth broken.

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
| **Previous SOP** | [SOP-DEP-002 — Production Deployment](SOP-DEP-002-production-deployment.md) |
| **Next SOP** | [SOP-DEP-004 — Queue Workers](SOP-DEP-004-queue-workers.md) |
| **Related SOPs** | [SOP-DEP-008](SOP-DEP-008-ssl.md), [SOP-SEC-003](../security/SOP-SEC-003-credential-rotation.md) |
| **Related Documents** | [Deployment overview](../../deployment/overview.md) |
| **Required Forms** | Secrets change request |
| **Required Checklists** | Environment variable verification checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
