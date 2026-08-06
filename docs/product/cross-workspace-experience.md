# Deliverable 10 — Cross-Workspace Experience

How users move across workspaces without losing context. Aligns with [business-flows.md](./business-flows.md).

---

## Principle

NovaCRM is one platform. Cross-links should feel like **continuing work**, not restarting in another app.

Preserve: **who I am**, **which org**, **which record**, **why I came** (referrer).

---

## Transition patterns

### A. Soft switch (recommended)

User clicks related entity in another workspace:

1. Workspace switcher updates  
2. Target record opens  
3. Context bar shows: `From: Opportunity · Acme Expansion` with Back  
4. Recents updated in both workspaces  

### B. Split context (power)

Open related record in new tab/window — no workspace steal on original.

### C. Guided handoff

Wizard for multi-step flows (Candidate → Employee; Opportunity Won → Project).

---

## Canonical journeys

### Revenue → Delivery → Cash

```
CRM: Lead → Opportunity → Customer
        ↓              ↓
   Projects: Project   CRM: Quotation → Invoice → Payment
        ↓
   Support (future): Ticket on Customer
```

| Hop | From → To | Retention |
|-----|-----------|-----------|
| Lead → Opportunity | CRM → CRM | Same workspace |
| Opportunity → Customer | CRM | Same |
| Opportunity → Project | CRM → Projects | Context bar + customer/opportunity IDs |
| Customer → Invoice | CRM | Same |
| Invoice → Payment | CRM | Same |
| Customer → Support | CRM → Support | Future |

### Talent → HR ops

```
HR: Candidate → Offer → Employee → Leave / Attendance / Payroll / Performance
```

| Hop | Retention |
|-----|-----------|
| Candidate → Employee | Guided handoff; link back to candidate |
| Employee → Leave | Same HR workspace; entity context |
| Employee → Payroll | Same; permission gated |
| Employee → Resource allocation | HR → Projects; employee id retained |

### People as resources

```
HR Employee ↔ Projects Resource allocation ↔ Tasks
```

Show “View in HR” / “View allocations” chips; do not duplicate full employee admin inside Projects.

---

## Navigation & chrome

| Element | Behavior on cross-link |
|---------|------------------------|
| Workspace switcher | Updates to target |
| Primary nav | Target workspace |
| Breadcrumbs | `Projects > …` (target); optional parent trail in context bar |
| Back | Context bar Back → return URL; else browser history |
| Search scope | Target workspace default |
| Unsaved changes | Block navigation with confirm |

---

## Context retention

Store in session / URL:

| Key | Purpose |
|-----|---------|
| `from_workspace` | Return target |
| `from_entity_type` + `from_entity_id` | Context bar label |
| `from_url` | Exact back |
| Linked foreign keys | Already on models |

Clear context bar when user explicitly dismisses or navigates elsewhere cold.

---

## Breadcrumbs vs context bar

- **Breadcrumbs** = location in current workspace IA  
- **Context bar** = why you arrived from another workspace  

Do not overload breadcrumbs with foreign workspace parents beyond one “from” chip.

---

## Linked entities UI

On entity headers/overview:

- Related chips with workspace icon  
- “Create related” actions (Create Project from Opportunity)  
- Relationship panels on Customer 360 / Employee  

Permission: hide links user cannot open.

---

## Back navigation

Priority:

1. Explicit **Back to {record}** in context bar  
2. **Back to list** on entity pages  
3. Browser history  
4. Workspace home  

Avoid hijacking browser back unexpectedly.

---

## Notifications & search deep links

Deep links must:

1. Resolve org  
2. Switch workspace  
3. Open entity/tab  
4. Fail gracefully with empty/permission state  

---

## Anti-patterns

- Opening foreign records inside an iframe of the wrong workspace  
- Losing Opportunity link when creating Project  
- Forcing full page reload that drops unsaved filters without warning  
- Circular “related” spam (limit visible links)  
