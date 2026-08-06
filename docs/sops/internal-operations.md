# SOP — Internal Operations

> **Superseded for execution by Phase 15.1.1 numbered SOPs.**  
> Use [INDEX.md](INDEX.md) → Release Management (`SOP-REL-001` … `SOP-REL-005`) and Security. This family document is retained for deep-link compatibility.

---
**Document control**
| Field | Value |
|-------|-------|
| Version | 1.1 |
| Owner | Operations |
| Review cadence | Quarterly |
| Last reviewed | 2026-07-25 |
| Status | Legacy reference (see INDEX) |

## Purpose
Govern how Konnect Nex ships changes: release management, versioning, QA, docs, security, and approvals.

## 1. Release management
1. Scope freeze for release candidate
2. Changelog / release notes draft
3. QA sign-off ([Release checklist](../operations/release-checklist.md))
4. Deploy via Technical Operations SOP
5. Post-release smoke + monitoring watch (24h)

## 2. Versioning
- Application tags follow SemVer where possible (`MAJOR.MINOR.PATCH`)
- SOP document control tracks process docs independently
- Breaking API changes require MAJOR + notes in `UPGRADE.md`

## 3. Change management
| Change type | Approval |
|-------------|----------|
| Docs / SOP only | Doc owner |
| Config / feature flag | Tech Lead |
| Schema migration | Tech Lead + backup confirmation |
| Security-sensitive | Security review + Tech Lead |
| Customer-impacting downtime | Sales/CS notified |

## 4. Code deployment
PR → review → CI green → merge → deploy artifact. No hot-fixes without ticket reference.

## 5. QA sign-off
- [ ] Automated tests relevant to change pass
- [ ] Smoke group for production deploys: `php artisan test --group=smoke`
- [ ] Manual smoke for touched workspaces ([smoke.md](../release/smoke.md))
- [ ] Regression notes attached to release

## 6. Documentation updates
Feature incomplete without docs updates under `docs/`. Update Program 15 ops docs when process changes.

## 7. Security reviews
Trigger for: auth, permissions, tenant scoping, uploads, webhooks, secrets, new public endpoints.

## 8. Internal approvals
Commercial exceptions follow Sales + Legal matrix in Sales Operations SOP.

## Exit criteria
Change merged with approvals recorded; production deploy only with QA sign-off for release trains.