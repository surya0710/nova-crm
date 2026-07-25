# SOP-DEP-002 — Production Deployment

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-DEP-002 |
| **Title** | Production Deployment |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Deployment |
| **Owner** | DevOps / Platform Engineer |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Deploy application code and schema changes to production safely with backup and smoke validation.

## Scope

- **In scope:** Code artifact deploy, migrate forward-only, cache warm, queue restart, and smoke.
- **Out of scope:** Release approval governance (SOP-REL-002) and rollback decision detail (SOP-REL-004 / SOP-DEP related).

## Preconditions

- [ ] Release approved (SOP-REL-002)
- [ ] Backup completed (SOP-MNT-002)
- [ ] Maintenance window communicated if downtime expected

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Production host | DevOps | Deploy artifact |
| Database | DevOps / Backend | Forward migrate only |

## Step-by-step Procedure

### 1. Pre-deploy

1. Follow [Production Deployment Checklist](../../operations/production-deployment-checklist.md).
2. Confirm backup complete.
3. `php artisan migrate:status` — review pending migrations.

### 2. Deploy

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

**Never** run `migrate:fresh`, `migrate:refresh`, `db:wipe`, or `migrate:reset` on shared/production databases.

### 3. Verify

1. Hit `GET /up` and critical login paths.
2. Confirm queue/scheduler healthy (SOP-MON-002 / SOP-MON-003).

## Validation Checklist

- [ ] Deploy logged on change ticket
- [ ] Health green (`/up`)
- [ ] Smoke passed
- [ ] Queues restarted
- [ ] No destructive migrate commands used
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Follow SOP-REL-004 Rollback: `php artisan down` if needed; redeploy previous artifact; restore DB only if migration irreversible and approved; `php artisan up`; RCA within 48 hours.

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
| **Previous SOP** | [SOP-DEP-001 — Server Provisioning](SOP-DEP-001-server-provisioning.md) |
| **Next SOP** | [SOP-DEP-003 — Environment Configuration](SOP-DEP-003-environment-configuration.md) |
| **Related SOPs** | [SOP-REL-003](../release-management/SOP-REL-003-production-deployment.md), [SOP-REL-004](../release-management/SOP-REL-004-rollback.md), [SOP-MNT-002](../maintenance/SOP-MNT-002-backup.md) |
| **Related Documents** | [Production Deployment Checklist](../../operations/production-deployment-checklist.md), [Deployment overview](../../deployment/overview.md), [Smoke](../../release/smoke.md) |
| **Required Forms** | Change / release ticket |
| **Required Checklists** | [Production Deployment Checklist](../../operations/production-deployment-checklist.md) |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
