# SOP-DEP-006 — Cache

| Field | Value |
|-------|-------|
| **SOP ID** | SOP-DEP-006 |
| **Title** | Cache |
| **Version** | 1.0 |
| **Effective Date** | 2026-07-25 |
| **Department** | Deployment |
| **Owner** | DevOps / Backend Lead |
| **Reviewer** | Tech Lead |
| **Approval** | Approved |
| **Status** | Active |

---

## Purpose

Warm and clear application caches so configuration and views remain consistent after changes.

## Scope

- **In scope:** Config/route/view caches and safe clear/rebuild sequences.
- **Out of scope:** Cache cleanup maintenance (SOP-MNT-005) and Redis/file store incidents.

## Preconditions

- [ ] Deploy or config change requiring cache refresh

## Required Access

| System / Console | Permission / Role | Notes |
|------------------|-------------------|-------|
| Application host | DevOps | Run artisan cache commands |

## Step-by-step Procedure

### 1. Warm caches (standard deploy)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2. After config change

```bash
php artisan config:clear && php artisan config:cache
```

## Validation Checklist

- [ ] Commands completed without error
- [ ] Application reflects new config
- [ ] No APP_DEBUG left enabled in production
- [ ] Evidence attached to the controlling ticket / change record

## Rollback Procedure

Clear caches again; restore prior config from vault; escalate if widespread 5xx.

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
| **Previous SOP** | [SOP-DEP-005 — Scheduler](SOP-DEP-005-scheduler.md) |
| **Next SOP** | [SOP-DEP-007 — Storage](SOP-DEP-007-storage.md) |
| **Related SOPs** | [SOP-MNT-005](../maintenance/SOP-MNT-005-cache-cleanup.md) |
| **Related Documents** | [Deployment overview](../../deployment/overview.md) |
| **Required Forms** | Change ticket |
| **Required Checklists** | Cache warm/clear checklist |

## Version History

| Version | Author | Date | Summary | Approval |
|---------|--------|------|---------|----------|
| 1.0 | Operations | 2026-07-25 | Initial release for Program 15.1.1 SOP library foundation | Operations Lead |

---

*Document control: SOP IDs never change once assigned. Propose revisions via PR to `docs/sops/`. Owner reviews within 5 business days.*
