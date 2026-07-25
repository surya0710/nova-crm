# Standard Operating Procedures (SOP) Library

**Phase:** 15.1.1 — SOP Library Foundation  
**Version:** 1.0  
**Effective Date:** 2026-07-25  
**Status:** Active  

Centralized, version-controlled operational procedures for selling, provisioning, deploying, onboarding, supporting, upgrading, and offboarding NovaCRM customers.

This library is the **single source of truth** for internal operations. Every SOP is written so a trained employee can execute it without tribal knowledge.

## Start here

| Resource | Purpose |
|----------|---------|
| [INDEX.md](INDEX.md) | Full catalog by department with stable SOP IDs |
| [templates/SOP_TEMPLATE.md](templates/SOP_TEMPLATE.md) | Reusable document standard for new SOPs |

## Directory structure

```
docs/sops/
├── README.md                 ← this file
├── INDEX.md                  ← master catalog
├── templates/                ← SOP template
├── sales/
├── onboarding/
├── implementation/           ← reserved (see folder README)
├── administration/
├── deployment/
├── support/
├── maintenance/
├── customer-success/
├── security/
├── billing/
├── migrations/               ← reserved (see folder README)
├── monitoring/
├── disaster-recovery/
├── release-management/
└── offboarding/
```

## Naming convention

IDs follow `SOP-<DEPT>-<NNN>` and **never change** once assigned.

Examples: `SOP-SAL-001`, `SOP-ONB-001`, `SOP-ADM-001`, `SOP-DEP-001`, `SOP-SUP-001`, `SOP-SEC-001`, `SOP-BIL-001`, `SOP-MON-001`, `SOP-MNT-001`, `SOP-REL-001`.

File names: `SOP-<DEPT>-<NNN>-<kebab-slug>.md`.

## Document standard

Every SOP includes:

- SOP ID, Title, Version, Effective Date  
- Department, Owner, Reviewer, Approval  
- Purpose, Scope, Preconditions, Required Access  
- Step-by-step Procedure, Validation Checklist, Rollback Procedure  
- Exceptions, Audit Trail  
- Cross references: Previous SOP, Next SOP, Related SOPs, Related Documents, Required Forms, Required Checklists  
- Version History (Version, Author, Date, Summary, Approval)

## Lifecycle map (high level)

```text
Lead → Discovery → Demo → Proposal → Pricing → Contract → Sales Handover
  → Onboarding → Go-Live → CS Welcome / Training / Health / QBR / Renewal
  → (Expansion | Churn save | Cancellation → Offboarding)
```

Platform path (parallel): Administration · Deployment · Monitoring · Maintenance · Release · Security · DR · Billing.

## Change process

1. Propose change via PR to `docs/sops/`.
2. Clone [SOP_TEMPLATE.md](templates/SOP_TEMPLATE.md) for net-new SOPs; register the ID in [INDEX.md](INDEX.md).
3. Owner reviews within 5 business days.
4. Bump **Version** and **Effective Date**; append **Version History**.
5. Announce material changes if customer-facing SLAs change.

## Engineering rules

- Documentation only (no application feature work in this library).
- Markdown only for SOP content.
- No duplicate SOP IDs or overlapping “sources of truth.”
- Clear operational language; executable checklists (`- [ ]`).
- Never rely on undocumented tribal knowledge — name vaults, owners, and systems.

## Legacy family documents

Consolidated family SOPs from earlier Program 15 work remain in this folder for deep-link compatibility. Prefer numbered SOPs in department folders for day-to-day execution. See [INDEX.md](INDEX.md) § Legacy family documents.
