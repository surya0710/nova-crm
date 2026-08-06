# Deliverable 7 — Activity Experience

Standardized activity feeds across Konnect Nex.

---

## Purpose

Answer: “What happened that I should know about?” — without opening Audit Log.

---

## Feed types

| Type | Scope | Audience | Examples |
|------|-------|----------|----------|
| **Personal** | Actor or assignee is me | User | Assigned task, my lead updated, mention |
| **Workspace** | Events in workspace domain | Workspace users | New opportunity stage, project health drop |
| **Entity** | Single record timeline | Viewers of record | Notes, status changes, files |
| **Organization** | Tenant-wide notable events | Admins / exec | Integration failure, mass import |
| **Department** | Team membership scope | Managers | Team leave approved, report progress |

---

## Event taxonomy

| Category | Description |
|----------|-------------|
| **Updates** | Field/status changes |
| **Mentions** | `@user` in comments/notes |
| **Comments** | Notes, task comments, evaluations |
| **Assignments** | Owner/assignee changes |
| **Approvals** | Leave, offer, workflow approvals |
| **System** | Automations, imports, sync |
| **Documents** | Attachments added |
| **Milestones** | Progress, health, payments recorded |

Each event: `actor`, `verb`, `object`, `timestamp`, `workspace`, `deep_link`, `visibility`.

---

## Timeline presentation

```
Today
 · Jane mentioned you on Task “API polish”
 · Opportunity Acme → Proposal
Yesterday
 · Payment recorded on INV-1042
```

Rules:

- Group by day (user timezone)  
- Reverse chronological  
- Relative time + absolute on hover  
- Actor avatar/initials  
- Icon by category  
- Click → entity (respect permissions)

---

## Surfaces

| Surface | Feed type | Density |
|---------|-----------|---------|
| Home widget “Recent activity” | Personal | 5–8 items |
| Workspace home activity | Workspace + Personal blend | 8–10 |
| Entity Activity tab | Entity | Infinite scroll / pages |
| Notifications inbox | Actionable subset | See notifications |
| Department dashboard | Department | 8 |
| Admin home | Organization | 8 |
| Audit Log | Full compliance stream | Filtered table (not a social feed) |

Audit Log remains the system of record for compliance; Activity is UX-oriented and may omit low-value noise.

---

## Mentions & comments

- Mentions create Personal feed items + Notifications  
- Comment threads live on entity; summarized in feed  
- Edit/delete comments per policy; feed shows “edited”  
- Project mentions reuse existing mentions subsystem  

---

## Assignments

Assignment events always appear on:

1. Assignee’s Personal feed  
2. Entity timeline  
3. Notification (Assignment section)  

---

## Filtering

| Filter | Values |
|--------|--------|
| Category | Updates, Mentions, Comments, Assignments, Approvals, System |
| Actor | Me · Anyone · Specific user |
| Time | Today · 7d · 30d · Custom |
| Module | Lead · Project · … |

Workspace feed defaults to workspace modules only.

---

## Permissions & privacy

- If user cannot view object, omit event (no “restricted item” teaser).  
- Salary/payroll events: strict payroll permissions.  
- Organization feed: admin/audit oriented permissions.

---

## Noise control

| Mechanism | Behavior |
|-----------|----------|
| Collapse bursts | “12 fields updated” → single event with expand |
| Mute entity | Stop Personal feed from this record |
| Workflow verbosity | System events off by default in Personal |
| Digest | Optional daily email — see notifications |

---

## Relationship to existing

- `RecentActivitiesWidgetProvider` / `RecentActivitiesController` → Personal/Home  
- Entity notes (LeadNote, etc.) → Entity timelines  
- Mentions routes → Mentions category  
- Audit log → Organization compliance, not primary UX feed  

Phase 14: unify event shape; do not require one DB table on day one — adapters OK.

---

## Empty & loading

- Empty: “No recent activity. When teammates update records you can access, they’ll show up here.”  
- Loading: skeleton rows  
- Error: Retry  

---

## Anti-patterns

- Mixing audit verbosity into Personal Home  
- Feeds that mark notifications read as a side effect unexpectedly  
- Auto-playing GIFs or noisy animations in timelines  
