# Release 1.2.3 — Task Board & Sprint Execution

**Release:** 1.2.3  
**Priority:** Critical  
**Status:** Implemented  

---

## Objective

Complete the day-to-day execution layer of Project Management.

Managers plan work visually; employees execute through an intuitive task board.

This release **extends** existing task functionality. Architecture is unchanged.

### Out of scope

- Scrum ceremonies (planning poker, retros, velocity charts)
- Payroll / Performance / Client Portal / AI
- Employee Profile Enrichment → **Release 1.2.4**

---

## Design principle

```
Controllers → Form Requests → Services → Models
```

| Rule | Implementation |
|------|----------------|
| No duplicate task logic | Board moves / quick actions call `TaskService` |
| Status columns config-driven | `config/tasks.board.columns` maps slugs → columns |
| Preferences reusable | `UserUiPreference.meta.task_board` |

---

## What was delivered

### 1. Enterprise Kanban Board

Drag-and-drop board for project tasks.

| Column | Default status slug(s) |
|--------|------------------------|
| Backlog | `backlog` |
| Todo | `to-do` |
| In Progress | `in-progress` |
| Review | `review` |
| Testing | `testing` |
| Done | `completed` |

Users can:

- Drag cards between columns  
- Reorder within a column  
- Filter instantly (GET filters + saved views)  

Updates are JSON (`tasks.board.move`) — no full page reload.

**Service:** `App\Services\TaskBoardService`  
**UI:** `/tasks/board` → `resources/views/tasks/board.blade.php`

New seeded statuses: `backlog`, `testing` (via `TaskDefaultsService` / `config/tasks.default_statuses`).

---

### 2. Swimlanes

Layouts: **None · Assignee · Priority · Milestone · Sprint · Status**

Selected swimlane persisted in `user_ui_preferences.meta.task_board.swimlane`.

---

### 3. Task card improvements

Each card shows:

- Title · Priority · Assignee avatar · Due date  
- Progress % · Checklist progress  
- Estimated / logged hours  
- Attachment count · Comment count  
- Dependency indicator · Overdue badge  

Full task page opens only via **Details** / explicit open — not on every card interaction.

**Partial:** `resources/views/tasks/partials/board-card.blade.php`

---

### 4. Quick actions (no reload)

| Action | Mechanism |
|--------|-----------|
| Change status | `TaskService::update` |
| Assign user | `TaskService::assign` |
| Update priority | `TaskService::update` |
| Log time | `TimeTrackingService::logManual` |
| Add checklist item | `ChecklistService::create` |
| Add comment | `TaskCommentService::create` |
| Open details | Navigate to `tasks.show` |

**Route:** `POST tasks/{task}/board/quick-action`

---

### 5. Sprint foundation

Lightweight sprints (no ceremonies).

| Field | Notes |
|-------|--------|
| Name | Required |
| Goal | Optional |
| Start / end date | Optional |
| Status | `planned` · `active` · `completed` · `cancelled` |

Tasks may optionally set `sprint_id`.

**Migration:** `2026_07_28_220000_create_sprints_and_board_support.php`  
**Model / service:** `Sprint`, `SprintService`  
**UI:** `/sprints`

---

### 6. Backlog management

Dedicated backlog at `/tasks/backlog`.

| Capability | How |
|------------|-----|
| Prioritize | Drag reorder → `BacklogService::reorder` |
| Drag / assign to sprint | Select + `moveToSprint` |
| Assign to milestone | Select + `moveToMilestone` |
| Bulk assign | Form + `bulkAssign` |
| Bulk priority | Form + `bulkPriority` |

Also registers platform bulk action `task.assign`.

---

### 7. Board filters

Project · Sprint · Milestone · Assignee · Status · Priority · Labels · Due range · Overdue only  

Filters merge with stored preferences on load.

---

### 8. Saved views

Examples: “My Tasks”, “Current Sprint”, “High Priority”.

Stored under `meta.task_board.saved_views` with name, filters, and swimlane.  
Saved via `POST tasks/board/preferences`.

---

### 9. Board metrics

Live widgets above the board:

Total · Todo · In Progress · Review · Done · Overdue · Avg completion % · Estimated hours · Logged hours  

Refreshed client-side after successful drag-and-drop (metrics payload from move API). Short-TTL cache available via `TaskBoardService::cachedMetrics`.

---

### 10. WIP limits

Optional `task_statuses.wip_limit`.

When exceeded:

- Column highlighted (amber ring)  
- Managers notified (`CrmNotification`)  

**No blocking** in MVP (`config/tasks.board.wip_notify`).

WIP editable through status Form Request / `TaskStatusService`.

---

## Architecture map

| Layer | Components |
|-------|------------|
| Services | `TaskBoardService`, `SprintService`, `BacklogService` (+ existing `TaskService`, checklist/comment/time) |
| Controllers | `TaskBoardController`, `SprintController`, `BacklogController` |
| Policy | `SprintPolicy` |
| Config | `config/tasks.php` → `board`, `sprint_statuses`, extended `default_statuses` |
| Views | `tasks/board`, `tasks/partials/board-card`, `tasks/backlog/index`, `tasks/sprints/index` |

---

## Routes

| Name | Purpose |
|------|---------|
| `tasks.board` | Kanban UI |
| `tasks.board.move` | Drag / reorder |
| `tasks.board.quick-action` | Inline actions |
| `tasks.board.preferences` | Swimlane / saved views |
| `tasks.backlog` | Backlog UI |
| `tasks.backlog.reorder` / `.move` / `.bulk` | Backlog operations |
| `sprints.index` / `.store` / `.update` / `.destroy` | Sprint CRUD |

---

## Acceptance criteria

| Criterion | Status |
|-----------|--------|
| Enterprise Kanban board operational | ✓ |
| Sprint foundation implemented | ✓ |
| Backlog management available | ✓ |
| Rich task cards displayed | ✓ |
| Inline task actions functional | ✓ |
| Filters and saved views persist | ✓ |
| Board metrics update automatically | ✓ |
| WIP limits configurable | ✓ |
| Existing task architecture unchanged | ✓ |

---

## Testing

| Test | Covers |
|------|--------|
| `tests/Feature/TaskBoardSprintTest.php` | Board render, move JSON, sprint assign, backlog page, saved views |

```bash
php artisan migrate
php artisan test --filter=TaskBoardSprintTest
```

> Note: Feature tests need a clean `novacrm_testing` database. A corrupted test schema (FK errno 150 on `organization_user`) will cause RefreshDatabase failures unrelated to board code.

---

## Manual verification

1. Open **Task Board** — confirm Backlog → Done columns.  
2. Drag a card to **In Progress** — metrics update, no full reload.  
3. Use a quick action (assign / comment).  
4. Save a named view; reload and confirm filters.  
5. Create a sprint; from **Backlog**, assign tasks to it.  
6. Set a status WIP limit; overload the column and confirm highlight / notify.

---

## Next release

**Release 1.2.5 — HRMS Final Stabilization & Production Readiness**

- UX polish across HRMS  
- Employee self-service refinements  
- Cross-module regression testing  
- Performance optimization  
- Permission audit  
- Mobile responsiveness  
- Final production QA  

See also: [Employee Profile & Career Management (1.2.4)](../hrms/employee-profile-career.md)  
