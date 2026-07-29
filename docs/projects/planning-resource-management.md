# Release 1.2.2 — Project Planning & Resource Management

**Release:** 1.2.2  
**Priority:** Critical  
**Status:** Implemented  

---

## Objective

Complete the planning layer of Project Management so managers can plan work, monitor team capacity, identify bottlenecks, and track delivery timelines.

Focus: **project execution visibility**.

### Out of scope

- Payroll
- Performance Management
- Client Portal
- AI
- Budgeting
- Billing

---

## Design principle

```
Controllers → Form Requests → Services → Models
```

Business rules live in services. Controllers orchestrate only. Existing services were extended; calendar/capacity/health logic was not duplicated.

---

## What was delivered

### 1. Project Calendar

Unified live calendar for every project (not only synced `ProjectCalendarLink` rows).

| Display | Source |
|---------|--------|
| Tasks | `Task` due / start dates |
| Milestones | `ProjectMilestone.due_date` |
| Team leave | Approved leave via leave applications |
| Public holidays | `Holiday` |
| Sprint dates | Future-ready (legend reserved) |

**Views:** Month · Week · Agenda  

**Filters:** Project · Employee · Status · Priority  

Task events open the existing task detail route.

**Service:** `App\Services\ProjectCalendarService`  
**UI:** `projects.calendar` → `resources/views/projects/calendar/index.blade.php`  
**API:** `GET /api/v1/projects/calendar?planning=1` (or `source=planning`)

External sync (`CalendarSyncService`) remains available via project calendar sync.

---

### 2. Resource Allocation Dashboard

Manager workload dashboard driven by `WorkloadService::allocationDashboard()`.

Each employee row shows:

| Field | Meaning |
|-------|---------|
| Active Projects | Active `ProjectMember` count |
| Active Tasks | Open assigned tasks |
| Estimated Hours | Sum of task `estimated_hours` |
| Logged Hours | Task actual / time logs |
| Remaining Hours | Estimated − logged |
| Capacity % | Utilization vs available hours |
| Status | Healthy · Available · Overallocated |

Capacity thresholds remain configurable in `config/resources.php`.

**Route:** `resources.capacity`

---

### 3. Team Workload View

| Period | Default range |
|--------|----------------|
| Daily | Today |
| Weekly | Current week |
| Monthly | Current month |

**Charts / summaries**

- Tasks per employee  
- Hours per employee  
- Remaining workload  
- Upcoming deadlines  

**Filters:** Project · Department · Branch  

**Service methods:** `teamWorkloadCharts()`, `allocationDashboard()`

---

### 4. Project Dashboard Enhancements

Progress dashboard widgets (`projects.progress.dashboard`):

- Project progress / health  
- Open · Completed · Delayed tasks  
- Hours logged · Remaining hours  
- Team capacity %  
- Milestone progress  
- Risks / overdue items  
- Recent activity (existing progress updates)

Org projects dashboard quick links added for Calendar, Capacity, and Planning Reports.

---

### 5. Project Health Indicator

Scoring remains in `ProjectHealthService` (no controller logic).

**Inputs**

- Overdue tasks  
- Completion %  
- Team capacity (new)  
- Milestone delays  
- Schedule variance  

**Display labels** (`config/projects.health_display`)

| Internal status | Manager label |
|-----------------|---------------|
| `on_track` | Healthy |
| `at_risk` | At Risk |
| `delayed` | Critical |

Managers are notified when health becomes Critical (`delayed`).

---

### 6. Task Dependency Visualization & Enforcement

**UI** (`tasks.dependencies.index`)

- Dependency chain: Task A → Blocks → Task B → …  
- **Blocked By** panel: task name, assignee, status  

**Service:** `TaskDependencyService`

| Method | Purpose |
|--------|---------|
| `dependencyChain()` | Visual successor chain |
| `blockedBySummary()` | Incomplete predecessors |
| `assertCanComplete()` | Blocks completion when configured |

**Config**

```php
// config/tasks.php
'enforce_dependency_blocking' => env('TASKS_ENFORCE_DEPENDENCY_BLOCKING', true),
'blocking_dependency_types' => ['finish_to_start'],
```

Wired into `TaskService::complete()` and status updates that complete a task.

Creating a blocking dependency notifies assignee / project manager / owner.

---

### 7. Milestone Progress

Every milestone surfaces (from task completion):

- Progress %  
- Tasks total / completed / remaining  
- Target date  
- Overdue badge  

**Service:** `MilestoneProgressService::forMilestone()`  
**UI:** `projects.milestones.index`

---

### 8. Workload Timeline

Per-employee timeline (`resources.timeline` + employee workload page):

```
Employee
  → Current tasks
  → Future tasks
  → Leave
  → Free capacity
```

**Service:** `WorkloadService::employeeTimeline()`

---

### 9. Notifications

| Event | Mechanism |
|-------|-----------|
| Capacity exceeded | Existing `ResourceAllocationService` / capacity events |
| Milestone overdue | Existing `MilestoneProgressService` → `MilestoneDelayed` |
| Health → Critical | `ProjectHealthService::notifyCriticalHealth()` |
| Dependency blocks task | `TaskDependencyService::notifyBlockedDependency()` |

Uses `CrmNotification` (same pattern as other CRM alerts).

---

### 10. Reports

Org-level planning reports (Attendance Report architecture):

| Report type | Key |
|-------------|-----|
| Resource Allocation Report | `resource_allocation` |
| Project Progress Report | `project_progress` |
| Workload Report | `workload` |
| Milestone Report | `milestone_report` |

**Export:** CSV · Excel (XLSX) · PDF  

**Routes**

- `projects.planning.reports.index`  
- `projects.planning.reports.export`  

**Services:** `PlanningReportService`, `PlanningReportExportService`  

Per-project reports also gained aliases: `resource_allocation`, `project_progress`, `workload`, `milestone_report` on `ProjectReportingService`.

---

## Architecture map

| Layer | Components |
|-------|------------|
| Services | `ProjectCalendarService`, `WorkloadService`, `ProjectHealthService`, `MilestoneProgressService`, `TaskDependencyService`, `PlanningReportService`, `PlanningReportExportService` |
| Controllers | `ProjectCalendarController`, `ResourcePlannerController`, `ProjectProgressDashboardController`, `ProjectMilestoneController`, `TaskDependencyController`, `PlanningReportController` |
| Config | `config/projects.php`, `config/resources.php`, `config/tasks.php` |
| Views | `projects/calendar`, `resources/capacity`, `resources/timeline`, `resources/workload`, `projects/milestones`, `tasks/dependencies`, `projects/progress/dashboard`, `projects/planning/reports` |

---

## Performance & tenancy

- Aggregations in services (batched task / time-log / membership queries)  
- Dashboard widgets remain lazy-loadable via existing widget architecture  
- All queries scoped by organization / tenant context  
- Heavy calendar ranges limited to selected view window  

---

## Acceptance criteria

| Criterion | Status |
|-----------|--------|
| Managers visualize schedules via Project Calendar | ✓ |
| Employee workload and capacity visible | ✓ |
| Project health calculated automatically | ✓ |
| Task dependencies enforced and visible | ✓ |
| Milestone progress updates from tasks | ✓ |
| Dashboard provides actionable project KPIs | ✓ |
| Resource / project reports export (CSV/Excel/PDF) | ✓ |
| Calculations are service-driven and reusable | ✓ |

---

## Testing

| Test | Covers |
|------|--------|
| `tests/Feature/ProjectPlanningCalendarTest.php` | Calendar rendering with tasks / milestones / holidays |
| `tests/Feature/TaskDependencyBlockingTest.php` | Block completion + Blocked By UI |
| `tests/Feature/PlanningReportTest.php` | Report index + CSV export |
| `tests/Feature/ProjectMilestoneProgressTest.php` | Progress API fields (extended) |

Existing suites still cover health, resources, reporting, and RBAC.

Run (MySQL must be available):

```bash
php artisan test --filter="ProjectPlanningCalendarTest|TaskDependencyBlockingTest|PlanningReportTest"
```

---

## How to verify manually

1. Open **Projects → Calendar** — switch Month/Week/Agenda; filter by project.  
2. Open **Resources → Capacity** — confirm hours and status labels.  
3. Open a project **Progress Dashboard** — confirm KPI cards and health label.  
4. Link two tasks with Finish-to-Start; try completing the successor (should fail).  
5. Open **Projects → Planning Reports** — generate and export CSV/Excel/PDF.  

---

## Next release

**Release 1.2.3 — Employee Profile Enrichment**

- Skills · Certifications · Education · Experience  
- Emergency Contacts · Employee Timeline  
- Current Project / Attendance / Leave summaries  
- Reporting Structure · Profile Completion Score  
