 # Konnect Nex Documentation

This `docs/` tree collects product, architecture, API, deployment, SOP, and phase documentation for Konnect Nex.

The goal is to make it easy for **developers**, **operators**, and **recruiters/reviewers** to find what they need without exposing unnecessary internal history on the front page.

---

## For developers

- **Product & module overview**
  - Overall module map: [`product/module-audit.md`](product/module-audit.md)
  - Module classifications: [`product/module-classification.md`](product/module-classification.md)
- **Architecture**
  - Platform & workspace architecture: [`architecture/workflow.md`](architecture/workflow.md)
  - HRMS domain architecture: [`hrms/architecture/overview.md`](hrms/architecture/overview.md)
  - Frontend architecture and migration: [`FRONTEND.md`](FRONTEND.md), [`frontend/migration-progress.md`](frontend/migration-progress.md)
  - Coding standards and backend guidelines: [`developer/coding-standards.md`](developer/coding-standards.md), [`developer/architecture-principles.md`](developer/architecture-principles.md)
- **Services, RBAC & multi-tenancy**
  - Dynamic RBAC and permission flow: [`developer/rbac.md`](developer/rbac.md)
  - Organization provisioning and scope: [`developer/employee-provisioning.md`](developer/employee-provisioning.md), `OrganizationScope` and related docs referenced from Phase 13 stabilization.
- **APIs**
  - API overview and conventions: [`api/overview.md`](api/overview.md)
  - CRM/Projects/Resources APIs: [`projects/apis.md`](projects/apis.md), [`resources/apis.md`](resources/apis.md)
  - Imports and exports APIs: [`api/imports.md`](api/imports.md), [`exports/troubleshooting.md`](exports/troubleshooting.md)
  - Mobile and HRMS APIs: `mobile/README.md`, `hrms/overview.md`, and linked API guides.
- **Database & data contracts**
  - Metadata and marketing contracts: e.g. [`MARKETING_ATTRIBUTION_CONTRACT.md`](MARKETING_ATTRIBUTION_CONTRACT.md), [`MARKETING_CHANNEL_CLASSIFICATION_CONTRACT.md`](MARKETING_CHANNEL_CLASSIFICATION_CONTRACT.md)
  - HRMS and portfolio runtime contracts: [`HRMS_PLATFORM_RUNTIME_CONTRACT.md`](HRMS_PLATFORM_RUNTIME_CONTRACT.md), [`P12_PHASE_12_6_PORTFOLIO.md`](P12_PHASE_12_6_PORTFOLIO.md)
- **Testing**
  - Test commands are documented in the root `README.md` (`php artisan test`, grouped suites).
  - Phase 13 stabilization and this Phase 13.1 document track regression and coverage status: see [`P13_PHASE_13_ENTERPRISE_STABILIZATION_PROGRESS.md`](P13_PHASE_13_ENTERPRISE_STABILIZATION_PROGRESS.md) and [`P13_PHASE_13_1_REPOSITORY_SECURITY_RELEASE_AUDIT_PROGRESS.md`](P13_PHASE_13_1_REPOSITORY_SECURITY_RELEASE_AUDIT_PROGRESS.md).

---

## For operators (SRE / ops / administrators)

- **Deployment**
  - End-to-end deployment guide: [`deployment/overview.md`](deployment/overview.md)
  - Launch program documentation: [`launch/README.md`](launch/README.md)
  - Production readiness checklist: [`release/production-readiness.md`](release/production-readiness.md)
- **Operations & SOPs**
  - SOP index: [`sops/README.md`](sops/README.md)
  - Business operations SOPs: [`sops/business-operations/README.md`](sops/business-operations/README.md)
  - Monitoring & health checks: [`sops/monitoring/SOP-MON-001-daily-health-check.md`](sops/monitoring/SOP-MON-001-daily-health-check.md)
  - Maintenance & backups: disaster recovery and maintenance SOPs under `sops/disaster-recovery/` and `sops/maintenance/`
  - Customer onboarding & offboarding: `sops/onboarding/`, `sops/offboarding/`
- **Queues, schedulers & jobs**
  - Queue and scheduler expectations are described in `P13_PHASE_13_ENTERPRISE_STABILIZATION_PROGRESS.md` (Queue & Background Jobs sections) and in the deployment/monitoring SOPs above.
- **Organization setup**
  - Organization and workspace provisioning: onboarding guides under [`onboarding/`](onboarding/), including `go-live-checklist.md` and `wizard.md`
  - HRMS and CRM admin guides: `hrms/admin-guide/overview.md`, `crm/overview.md`, `navigation/guide.md`

---

## For recruiters and reviewers

- **Product overview**
  - High-level module overview and capabilities: [`product/module-audit.md`](product/module-audit.md)
  - Workspace-level guides for CRM, HRMS, Projects, Resources, and Portfolio under `crm/`, `hrms/`, `projects/`, `resources/`, `tasks/`, and related folders.
- **Architecture overview**
  - Platform and workflow architecture: [`architecture/workflow.md`](architecture/workflow.md)
  - Marketing, analytics, and dashboard architecture: `architecture/marketing.md`, `dashboard/administrator-guide.md`, `dashboard/widget-development-guide.md`
- **Technology stack**
  - Backend and frontend stack: root `README.md` (Requirements & Stack), [`FRONTEND.md`](FRONTEND.md)
- **Testing and quality**
  - Enterprise stabilization progress (Phase 13.0): [`P13_PHASE_13_ENTERPRISE_STABILIZATION_PROGRESS.md`](P13_PHASE_13_ENTERPRISE_STABILIZATION_PROGRESS.md)
  - Repository security & release audit (Phase 13.1): [`P13_PHASE_13_1_REPOSITORY_SECURITY_RELEASE_AUDIT_PROGRESS.md`](P13_PHASE_13_1_REPOSITORY_SECURITY_RELEASE_AUDIT_PROGRESS.md)
  - Release readiness and launch checklists: [`release/production-readiness.md`](release/production-readiness.md), [`launch/README.md`](launch/README.md)

---

## Phase and historical documents

- **Active phases**
  - Phase 13.0 — Enterprise Stabilization & Production Readiness: [`P13_PHASE_13_ENTERPRISE_STABILIZATION_PROGRESS.md`](P13_PHASE_13_ENTERPRISE_STABILIZATION_PROGRESS.md)
  - Phase 13.1 — Repository Security & Release Audit (this phase): [`P13_PHASE_13_1_REPOSITORY_SECURITY_RELEASE_AUDIT_PROGRESS.md`](P13_PHASE_13_1_REPOSITORY_SECURITY_RELEASE_AUDIT_PROGRESS.md)
- **Historical phases**
  - Historical phase and impact reports (P3–P14) are indexed under [`phases/archive/`](phases/archive/README.md) and remain at their original paths under `docs/` for backwards-compatible links.

