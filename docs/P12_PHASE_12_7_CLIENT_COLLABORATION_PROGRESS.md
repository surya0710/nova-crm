# P12 Phase 12.7 — Client Collaboration & Project Portal Progress

## Phase
Phase 12.7 — Client Collaboration & Project Portal

## Outcome
Organization-scoped client-facing project portal on top of Projects and CRM: separate `ClientUser` auth, granular project sharing, deliverables with versions and client approvals, client discussions, upload requests, shared invoice visibility, portal notifications, RBAC, metadata, workflow events, REST/portal APIs, Blade UI, documentation, and tests.

Internal Collaboration Center (Phase 12.5) remains staff-only and is not exposed to clients.

## Delivered

| Area | Status |
| --- | --- |
| Tables (`client_users`, `client_project_access`, `project_shared_links`, `deliverables`, `deliverable_versions`, `client_approvals`, `client_discussions`, `client_upload_requests`, `client_notifications`, `client_portal_settings`) | Done |
| Models + factories | Done |
| Services (`ClientAccessService`, `ProjectSharingService`, `DeliverableService`, `ApprovalService`, `DiscussionService`, `PortalNotificationService`, `ClientPortalFacadeService`) | Done |
| Domain events + workflow triggers (`deliverable.*`, `client.approved/rejected`, `discussion.created`, `portal.accessed`) | Done |
| RBAC (`portal.view`, `portal.manage`, `client.approve`, `deliverable.manage`) + permission sync migration | Done |
| Metadata entities (`deliverable`, `client_discussion`, `client_approval`) | Done |
| Portal auth (`client` guard) + middleware | Done |
| Staff UI (invite/access, deliverables) + Client portal Blade UI | Done |
| Portal REST API (`/api/v1/portal/{org}/…`) | Done |
| Module `customer_portal` wiring | Done |
| Unit/feature tests | Done |

## Architecture

```
Controllers → Form Requests → ClientPortalFacadeService (orchestration only)
        → ClientAccess / Sharing / Deliverable / Approval / Discussion / PortalNotification
        → AttachmentService / InvoiceService (read) / NotificationService
```

## Run

```bash
php artisan migrate
php artisan test tests/Unit/DeliverableServiceTest.php tests/Feature/ClientPortalTest.php
```

## Portal URL

`/{organization-slug}/portal/login`

## Notes
- Clients authenticate via the `client` guard (`ClientUser`), not org `User` / ESS portal flags.
- Project access requires explicit grants; `Project.client_id` alone does not expose internals.
- Deliverable approval lifecycle: draft → submitted → client_review → approved | rejected → revised.
- Shared invoices are read-only CRM invoices for the client's customer when the `invoices` scope is granted.
- No e-sign, live chat, white-label domains, or payment gateway (out of scope).

## Related
- [P12_PHASE_12_5_COLLABORATION.md](P12_PHASE_12_5_COLLABORATION.md) — internal collaboration (not client-facing)
- [projects/overview.md](projects/overview.md)
