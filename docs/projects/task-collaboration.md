# Release 1.2.1 — Employee Task Collaboration & Access

**Release:** 1.2.1  
**Phase:** 2.1  
**Priority:** Critical  
**Status:** Implemented  

---

## Objective

Complete the employee task experience so any user **assigned to a task** can execute work without project-manager intervention.

Scope is limited to:

- Task collaboration
- Assignment-driven permissions
- Project execution workflow

Out of scope: project administration, payroll, AI, and profile enrichment (Release 1.2.2).

---

## Problem

Assigned employees could view tasks but could not effectively work on them:

- No checklist create/update
- No comments
- No attachments
- No time logging
- Collaboration gated only by RBAC roles (e.g. `employee` had `tasks.view` only)

---

## Design principle

```
Project Managers
    ↓
Manage Project → Assign Tasks
    ↓
Employees execute only on assigned tasks
```

Permissions are granted when:

```
Employee → Assigned To Task → Allowed Task Actions
```

Even if the employee is not a Project Manager.

---

## Access model

Implemented in `App\Services\TaskAuthorizationService`.

| Layer | Rule |
|-------|------|
| 1. Dynamic RBAC | Base permission such as `tasks.view` |
| 2. Organization isolation | Tenant context / `BelongsToOrganization` |
| 3. Project membership | Active `ProjectMember` for project-bound tasks (elevated roles and assignees bypass) |
| 4. Task assignment | `tasks.assigned_to === auth user id` unlocks collaborator capabilities |

Elevated access (`tasks.manage`, `tasks.edit`, `tasks.update`) retains full task management.

---

## Capabilities matrix

### Assigned employee — allowed

| Action | Mechanism |
|--------|-----------|
| View task | `TaskPolicy@view` |
| Update own status / progress | `updateOwnWork` — fields: `status`, `status_id`, `completion_percentage` |
| Start / complete task | Status update + `tasks.complete` |
| Create / edit / complete checklist | `manageChecklists` → `ChecklistService` |
| Reorder checklist | `ChecklistService::reorder` |
| Progress % from checklist | `TaskService::calculateProgress` |
| Add comments / replies / mentions | `comment` → `TaskCommentService` |
| Edit / delete own comments | Own-author check in policy + service |
| Upload / download attachments | `attachments` (feature-flagged) |
| Delete own attachments | Uploader or elevated |
| Log time + Start / Pause / Resume / Stop | `timeLog` → `TimeTrackingService` |
| View Estimated / Actual / Remaining hours | Time-log UI |
| Activity / notifications | Existing audit, watchers, mention notifications |

### Assigned employee — denied

| Action | Enforcement |
|--------|-------------|
| Delete / archive project | Project policies unchanged |
| Delete / archive task | `tasks.delete` / `tasks.archive` required |
| Reassign task | Assignee update limited; `assigned_to` stripped for non-elevated |
| Change milestones / budget / members / settings | Full `update` required |
| Delete others’ comments | Manager / elevated only |
| Remove others’ attachments | Elevated or `tasks.attachments` |

---

## Modules

### Checklist

- Unlimited items per task
- Create / update / complete / delete
- Sequence / reorder support
- Task completion percentage recalculated from checklist completion

### Comments

- Threaded replies (`parent_comment_id`)
- Mentions via existing `MentionService`
- Edit own comments
- Delete own comments
- Manager / elevated moderation delete

### Attachments

Supported types (config): images, PDF, ZIP, Office documents.

Display: file name, uploader, date, size, download.

| Config key | Env | Default |
|------------|-----|---------|
| `attachments.enabled` | `ATTACHMENTS_ENABLED` | `true` |
| `attachments.task_attachments_enabled` | `TASK_ATTACHMENTS_ENABLED` | inherits global |

When disabled: UI link hidden; upload endpoints forbidden / 404.

### Time tracking

| Control | Route (web) | Route (API) |
|---------|-------------|-------------|
| Start | `POST tasks/{task}/time-logs/start` | same under `/api/v1` |
| Pause | `POST tasks/{task}/time-logs/pause` | same |
| Resume | `POST tasks/{task}/time-logs/resume` | same |
| Stop | `POST tasks/{task}/time-logs/stop` | same |
| Manual log | `POST tasks/{task}/time-logs` | same |

Sources: `timer`, `paused`, `manual`.

Employees edit/delete only their own entries (unless elevated).

### Timeline / notifications

No duplicate timeline engine. Existing infrastructure records and notifies:

- Assignments, status changes, checklist completion
- Comments, mentions, attachments
- Time logged

Via `AuditLogger`, `WatcherService`, and domain events.

---

## Architecture (reuse only)

```
TaskController / Form Requests
        ↓
TaskAuthorizationService
        ↓
TaskService | ChecklistService | TaskCommentService
| TaskAttachmentService | TimeTrackingService
        ↓
Models + WatcherService / Notification paths
```

No architectural rewrite. No duplicated business logic.

---

## Key files

| Path | Purpose |
|------|---------|
| `app/Services/TaskAuthorizationService.php` | Assignment-driven authorization |
| `app/Policies/TaskPolicy.php` | View / updateOwnWork / collaborate gates |
| `app/Policies/TaskChecklistPolicy.php` | Checklist gates |
| `app/Policies/TaskCommentPolicy.php` | Comment own-edit / moderate delete |
| `app/Policies/TaskAttachmentPolicy.php` | Attachment + feature flag |
| `app/Policies/TaskTimeLogPolicy.php` | Time-log ownership |
| `app/Http/Requests/UpdateTaskRequest.php` | Assignee field allow-list |
| `app/Services/TimeTrackingService.php` | Pause / resume |
| `config/attachments.php` | Task attachment feature flags |
| `tests/Feature/TaskAssigneeCollaborationTest.php` | Regression coverage |
| `docs/projects/task-collaboration.md` | This report |

---

## Acceptance criteria

| Criterion | Status |
|-----------|--------|
| Assigned employees can fully execute their tasks | Done |
| Checklist functionality complete for assignees | Done |
| Comments support replies and mentions | Done (existing + assignee access) |
| Attachments respect permissions and feature flags | Done |
| Timeline / notifications via existing infra | Done |
| Time tracking operational (incl. pause/resume) | Done |
| Assignment-driven authorization implemented | Done |
| RBAC + org isolation intact | Done |
| No duplicate business logic | Done |

---

## Testing

```bash
php artisan test --filter=TaskAssigneeCollaborationTest
```

Covered scenarios:

- Assignee status update without reassignment
- Checklist create / complete
- Comment create / edit own
- Unassigned project member denied comment
- Timer pause / resume / stop
- Attachment upload when enabled; blocked when flagged off
- Assignee cannot delete task

Also retain existing suite: `TaskCommentTest`, `TaskChecklistTest`, `TaskTimeLogTest`, `TaskAttachmentTest`, `TaskRbacTest`.

---

## Next release

**Release 1.2.2 — Employee Profile Enrichment**

- Skills & Certifications
- Education & Experience (UI completion)
- Emergency Contacts
- Employee Timeline enrichment
- Current Project Summary
- Profile Completion Score
