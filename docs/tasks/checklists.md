# Checklists

## Purpose
Describe checklist items on tasks: ordering, completion, and automatic progress updates.

## Model
`task_checklists` fields:

| Field | Role |
| --- | --- |
| `title` | Item label |
| `sequence` | Display/order index |
| `is_completed` | Completion flag |
| `completed_by` / `completed_at` | Completion audit |

Items are organization-scoped and cascade with the parent task. The `Task::checklists()` relation orders by `sequence`.

## Create
`ChecklistService::create()`:
- Requires a writable (non-archived, non-closed) task
- Defaults `sequence` to `max(sequence) + 1` when omitted
- Optionally creates already completed
- Calls `TaskService::calculateProgress()` afterward

## Ordering
- Explicit `sequence` on create/update
- `ChecklistService::reorder($task, $orderedIds, $actor)` assigns `sequence = index + 1` for the given ID list (service-level; no dedicated HTTP reorder route in Phase 12.2 — clients can PATCH `sequence`)

## Completion
`ChecklistService::complete($item, $actor, $completed = true)`:
- Toggles `is_completed` and completion audit fields
- Emits `ChecklistCompleted` when marking complete
- Refreshes task progress

Web complete route uses `PATCH` (`tasks.checklists.complete`).  
API complete route uses `POST` (`/api/v1/tasks/{task}/checklists/{checklist}/complete`).

## Auto-progress
`TaskService::calculateProgress()`:
1. If the task has neither checklists nor children, keeps existing `completion_percentage`
2. Otherwise averages:
   - checklist completion ratio × 100
   - average of children’s progress (closed children count as 100%)
3. Writes the rounded percentage to the task
4. Recurses to `parent_task_id` when present

Create, delete, and complete checklist operations all trigger this recalculation.

## Closed / archived
`assertTaskWritable()` rejects checklist mutations on archived or closed tasks.

## Routes
| Action | Web | API |
| --- | --- | --- |
| List | `GET tasks/{task}/checklists` | `GET /api/v1/tasks/{task}/checklists` |
| Create | `POST …` | `POST …` |
| Update | `PATCH …/{checklist}` | `PATCH …/{checklist}` |
| Delete | `DELETE …/{checklist}` | `DELETE …/{checklist}` |
| Complete | `PATCH …/{checklist}/complete` | `POST …/{checklist}/complete` |

Permission: `tasks.manage-checklists`.

## Related Documentation
See [lifecycle](lifecycle.md), [architecture](architecture.md), and [apis](apis.md).
