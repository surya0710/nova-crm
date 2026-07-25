# Deliverable 13 — Dashboard Success Metrics

Measurable UX goals for workspace and dashboard experience. Used to validate Phase 14+ implementations.

---

## North-star outcomes

1. Users see **today’s highest-priority work** within seconds of landing.  
2. Users complete **critical actions in one click** from workspace home.  
3. Users **switch workspace in one interaction**.  
4. Users **find primary actions within five seconds**.  
5. Dashboards stay **fast enough** that waiting is rare.

---

## Metric catalog

### Performance

| ID | Metric | Target | Measure |
|----|--------|--------|---------|
| P1 | Workspace home LCP / primary content visible | ≤ **2.5s** p75 on broadband reference | RUM / Lighthouse |
| P2 | Widget data time-to-meaningful (cached) | ≤ **1.0s** p75 | API timing |
| P3 | Widget data time-to-meaningful (uncached) | ≤ **3.0s** p75 | API timing |
| P4 | Soft refresh of KPI strip | ≤ **500ms** perceived | Client |
| P5 | Command palette open | ≤ **100ms** to interactive | Client |

Align cache with `DASHBOARD_CACHE_TTL` (often 300s) for heavy aggregates; counts may be fresher.

### Interaction efficiency

| ID | Metric | Target | Measure |
|----|--------|--------|---------|
| I1 | Critical action ≤ 1 click from workspace home | **100%** of catalogued Quick Actions | UX audit |
| I2 | Workspace switch interactions | **1** (select/click) | UX audit |
| I3 | Time to locate primary create action (new user test) | ≤ **5s** median | Moderated test |
| I4 | Clicks to open assigned task from Home | ≤ **2** | Task path audit |
| I5 | Deep link from notification to entity | ≤ **1** click after open | Audit |

### Clarity & priority

| ID | Metric | Target | Measure |
|----|--------|--------|---------|
| C1 | Attention items visible above fold (desktop 1080p) | ≥ **1** when any exist | Layout QA |
| C2 | Users can name “what needs me” after 5s glance | ≥ **80%** in test | Usability |
| C3 | Empty states show a primary CTA | **100%** module empties | Audit |
| C4 | Widgets without drill-down | **0** | Audit |

### Personalization & adoption

| ID | Metric | Target | Measure |
|----|--------|--------|---------|
| A1 | Users with customized layout (30d active) | Track baseline; no forced target Y1 | Analytics |
| A2 | Reset-to-default usage | Monitor for broken defaults | Analytics |
| A3 | Favorite usage rate | Track | Analytics |
| A4 | Search-with-scope vs Everywhere | Track | Analytics |

### Reliability & trust

| ID | Metric | Target | Measure |
|----|--------|--------|---------|
| R1 | Widget error rate | ≤ **1%** of widget loads | Logs |
| R2 | Notification click → 403 | **0** p95 | Logs |
| R3 | Cross-workspace link success | ≥ **99%** permitted | Logs |

### Accessibility

| ID | Metric | Target | Measure |
|----|--------|--------|---------|
| X1 | Critical a11y issues on Home/CRM/Projects | **0** | axe CI |
| X2 | Keyboard complete critical paths | **Pass** | Manual |

---

## Critical actions (for I1)

Must be one click from the relevant home when permitted:

- Create Lead, Create Opportunity, Create Task, Create Project  
- Approve Leave, Mark Attendance, Apply Leave  
- Record Payment, Invite User  

---

## Instrumentation (Phase 14+)

Event examples:

- `workspace.switched`  
- `dashboard.viewed`  
- `quick_action.clicked`  
- `widget.drilled`  
- `widget.error`  
- `search.performed` `{scope}`  
- `notification.opened`  

No PII in event props beyond ids.

---

## Acceptance for UX release

A workspace experience slice is “done” when:

- [ ] P1–P3 measured on staging reference hardware  
- [ ] I1–I3 validated for that workspace  
- [ ] C1–C4 audited  
- [ ] R1–R2 clean for sample week  
- [ ] X1 clean on changed surfaces  

---

## Anti-gaming

- Do not hide widgets to improve LCP if it removes Attention  
- Do not prefetch everything and call it interactive  
- Prefer honest skeletons over false “loaded” empty shells  
