# Tasks & Projects APIs

Mobile-accessible task and project endpoints. Requires `api.access` permission plus domain-specific permissions.

**Base path:** `/api/v1`  
**Middleware:** `auth:sanctum`, `throttle:api`, `set.organization`, `ensure.organization`, `organization.api`, `permission:api.access`

---

## Availability for HRMS Mobile

Tasks and projects are **optional** modules for HRMS mobile — include if organization licenses `projects` module and user has permissions.

Default `employee` role includes limited project/task read access.

---

## Tasks

### GET `/api/v1/tasks`

List tasks with filters.

**Permission:** `tasks.view` (via policy on individual records)

#### Query Parameters (`IndexApiTaskRequest`)

| Param | Type | Description |
|-------|------|-------------|
| `search` | string | Search title, description, task_number |
| `status` | string | Status slug |
| `status_id` | integer | Status ID |
| `priority` | string | Priority slug |
| `priority_id` | integer | Priority ID |
| `assigned_to` | integer | User ID — **use for "My Tasks"** |
| `project_id` | integer | Filter by project |
| `is_archived` | boolean | Archive filter |
| `filter` | string | `overdue` for overdue tasks |
| `page` | integer | Pagination |
| `per_page` | integer | Page size |

#### My Tasks Example

```http
GET /api/v1/tasks?assigned_to={current_user_id}&is_archived=false
```

#### Response

Laravel API Resource collection (paginated):

```json
{
  "data": [
    {
      "id": 1,
      "task_number": "TASK-001",
      "title": "Complete documentation",
      "description": "...",
      "status": "in_progress",
      "priority": "high",
      "due_date": "2026-07-25",
      "assigned_to": 5,
      "project_id": 10,
      "assignee": { },
      "task_status": { },
      "task_priority": { },
      "project": { }
    }
  ],
  "links": { },
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 42
  }
}
```

---

### GET `/api/v1/tasks/{task}`

Task detail.

**Permission:** `tasks.view` (policy)

---

### PATCH `/api/v1/tasks/{task}`

Update task (including status).

**Permission:** `tasks.update`  
**FormRequest:** `UpdateTaskRequest`

---

### POST `/api/v1/tasks/{task}/complete`

Mark task complete.

**Permission:** `tasks.update`

---

### POST `/api/v1/tasks/{task}/assign`

Assign task to user.

**Permission:** `tasks.update`  
**FormRequest:** `AssignTaskRequest`

---

### Task Sub-resources

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/tasks/{task}/comments` | List comments |
| POST | `/tasks/{task}/comments` | Add comment |
| GET | `/tasks/{task}/checklists` | Checklists |
| GET | `/tasks/{task}/attachments` | Attachments |
| POST | `/tasks/{task}/attachments` | Upload attachment |
| GET | `/tasks/{task}/time-logs` | Time logs |
| POST | `/tasks/{task}/time-logs/start` | Start timer |
| POST | `/tasks/{task}/time-logs/stop` | Stop timer |

---

## Projects

### GET `/api/v1/projects`

List projects.

**Permission:** `projects.view`

#### Response

Paginated project collection.

---

### GET `/api/v1/projects/{project}`

Project detail.

**Permission:** `projects.view`

---

### GET `/api/v1/projects/{project}/progress`

Project progress updates.

**Permission:** `projects.progress.view`

---

### GET `/api/v1/projects/{project}/statistics`

Project statistics summary.

**Permission:** `projects.view`

---

### GET `/api/v1/projects/watching`

Projects user is watching.

---

## Workload (HRMS-adjacent)

### GET `/api/v1/workload/employees/{employee}`

Employee workload/capacity data.

**Permission:** `projects.view` (typical)

Project capacity view — not HR attendance workload.

---

## Permissions Summary

| Permission | Slug |
|------------|------|
| API access | `api.access` |
| View tasks | `tasks.view` |
| Update tasks | `tasks.update` |
| Create tasks | `tasks.create` |
| View projects | `projects.view` |
| Update projects | `projects.update` |

Default `employee` role: `tasks.view`, `projects.view` (limited)

---

## Module License

Requires `projects` module licensed for organization.

---

## Mobile Screen Mapping

```
My Tasks Screen
  ↓
GET /tasks?assigned_to={user_id}
  ↓
GET /tasks/{id}
  ↓
PATCH /tasks/{id}  (status update)
  OR
POST /tasks/{id}/complete

My Projects Screen
  ↓
GET /projects
  ↓
GET /projects/{id}
```

---

## Not Available

- Mobile-optimized "my tasks" summary endpoint (use filtered list)
- HRMS-specific task integration (tasks are project module, not HRMS module)
