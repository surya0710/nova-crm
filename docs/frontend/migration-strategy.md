# Deliverable 13 — Migration Strategy

Rollout plan for applying Phases 13.1–13.4 in **Phase 14** UI implementation without a risky big-bang rewrite.

---

## Goals

- Incremental, reversible delivery  
- Legacy pages keep working during migration  
- Design-system components land behind aliases  
- Workspace IA ships in controlled order  

---

## Workspace rollout order

Align with product P0 gaps ([../product/phase14-backlog.md](../product/phase14-backlog.md)):

| Wave | Scope | Why |
|------|-------|-----|
| **0** | Foundations: tokens CSS vars, `ui.button`/`forms.field`, layout tokens | Unblocks all waves |
| **1** | App shell: workspace switcher, nav split (CRM / Projects / HR), demote dashboard clutter | Highest IA pain |
| **2** | CRM listing/detail templates + Quick Actions | High traffic |
| **3** | Projects workspace nav + homes (portfolios visible) | Hidden capability |
| **4** | HR grouped nav + My HR mode + Leave hub | Overload pain |
| **5** | Configuration Hub UI consolidation | Settings duplicates |
| **6** | Analytics / Admin homes + search/palette | Power user |
| **7** | Marketing MVP home | Lower traffic |
| **8** | Polish: density, dark opt-in, remaining aliases removal | Stabilization |

---

## Component migration

```
1. Add new component (ui.*)
2. Alias old tag to new implementation
3. Migrate callsites module-by-module
4. Remove alias + old file
```

Priority components: Button → Field → Card → Modal → SidebarLink → Table shell → Widget frame.

---

## Legacy compatibility

| Legacy | Strategy |
|--------|----------|
| Flat `components/*.blade.php` | Aliases until unused |
| Module-specific duplicated headers | Replace with `page-header` when touching file |
| `layouts/sidebar.blade.php` | Refactor in place first; extract components second |
| Inline indigo utilities | Accept until component provides variants |
| Feature CSS (`workflow.css`) | Keep scoped; don’t merge into app blindly |

Do not rename routes for UI-only waves.

---

## Feature flags

Prefer:

- Config / org settings flags for workspace switcher  
- Permission + plan gates already in place  
- Optional `features.workspace_nav` style config for staged enablement  

Flags default off on production until wave QA passes; enable per org if needed.

---

## Incremental deployment

1. Merge Wave 0 behind no visual change (aliases).  
2. Enable shell changes for internal org.  
3. Monitor errors (JS, 500s, a11y issues).  
4. Expand orgs / remove flag.  
5. Next wave.  

Each PR: small, checklist-complete, screenshots for shell changes.

---

## Rollback strategy

| Layer | Rollback |
|-------|----------|
| Flag | Disable feature flag → old nav |
| Component alias | Revert PR; aliases keep old markup path |
| CSS tokens | Variables fall back to hard-coded Tailwind classes still present |
| Vite assets | Redeploy previous build artifact |

Avoid irreversible Blade deletions until aliases show zero references (`rg` clean).

---

## Definition of done (migration wave)

- [ ] Product IA behavior for that wave met  
- [ ] Design checklist for touched templates  
- [ ] Frontend code review checklist  
- [ ] Responsive + keyboard smoke  
- [ ] No increase in JS error rate  
- [ ] Docs updated if patterns changed  

---

## Anti-patterns

- Rewriting all modules before shell exists  
- Removing Breeze components before aliases  
- Mixing unrelated refactors in wave PRs  
- Shipping dark mode mid-nav migration  

---

## Relationship to Phase 14 backlog

This migration strategy **sequences** [../product/phase14-backlog.md](../product/phase14-backlog.md) Themes A–G for engineering execution. Product backlog items remain the feature source of truth; this doc is the delivery mechanics.
