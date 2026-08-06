# Deliverable 3 — Alpine.js Standards

Alpine.js usage standards for NovaCRM (v3).

---

## When to use Alpine

| Use Alpine | Prefer server / full page |
|------------|---------------------------|
| Open/close menus, modals, drawers | Form submit creating records |
| Tabs, accordions, disclosure | Permission-gated page entry |
| Mobile sidebar toggle | Multi-step wizards with server validation (can hybrid) |
| Dropdown filters (UI state) | Heavy data grids needing virtualization libs (evaluate carefully) |
| Command palette UI chrome | Complex realtime apps (use Livewire/Echo only if adopted later) |
| Optimistic UI for trivial toggles | Anything requiring strong audit before paint |

**Default:** render HTML in Blade; Alpine enhances.

---

## When server rendering is preferred

- First contentful paint of listings/details  
- RBAC-filtered navigation  
- Flash messages after PRG redirects  
- SEO-irrelevant but **consistency** and **a11y** of forms  

Progressive enhancement: if Alpine fails, links/buttons still navigate where possible.

---

## Bootstrap

- Start Alpine once from `resources/js/app.js`  
- Use `Alpine.plugin` sparingly  
- `[x-cloak]` CSS remains in `app.css`  

---

## Component lifecycle

| Directive | Use |
|-----------|-----|
| `x-data` | Local state factories |
| `x-init` | Lightweight setup; no heavy network without UX |
| `x-effect` | Rare; avoid loops |
| `$watch` | Sync related fields |
| `x-destroy` cleanup | Remove listeners if added manually |

Prefer functions in `resources/js/components/*.js` registered as `Alpine.data('name', …)`.

---

## Stores

Use `Alpine.store` for **cross-component chrome**:

| Store | Responsibility |
|-------|----------------|
| `sidebar` | `open` mobile drawer, collapsed desktop |
| `toast` | push/dismiss toasts |
| `commandPalette` | open, query, mode |
| `workspace` | active workspace id (if client-visible) |

Do not put entity CRUD caches in Alpine stores.

---

## Events

| Pattern | Use |
|---------|-----|
| `$dispatch('nova:toast', …)` | App-wide feedback |
| `window` events | Rare bridge to non-Alpine scripts |
| Naming | `nova:{domain}:{action}` |

Document new global events in PR description.

---

## State management rules

1. UI state local to `x-data`  
2. Shared chrome → store  
3. Server data → Blade props / `@js` small payloads  
4. Avoid duplicating large collections in Alpine when Blade already rendered them  

---

## AJAX interaction

| Approach | When |
|----------|------|
| Axios (`bootstrap.js`) | JSON endpoints, dashboard widget refresh |
| `fetch` | Simple GET |
| Full form POST | Creates/updates default |
| Alpine + partial HTML | Only with clear morph/replace strategy (avoid ad-hoc `innerHTML` XSS) |

Always send CSRF (`X-CSRF-TOKEN` / cookie). Handle 403/422 with toasts + field errors.

---

## Progressive enhancement

- Modals: trigger is a `<button>`; content in DOM or loaded on open  
- Sidebar: CSS-friendly off-canvas; Alpine toggles class  
- Tabs: consider radio/CSS or ensure server can deep-link `?tab=` without JS  

---

## Performance

- Do not put Alpine on thousands of nodes (e.g. every table cell)  
- Prefer event delegation patterns for large lists  
- Defer feature scripts (`features/*`) until needed  

---

## Anti-patterns

- Rebuilding SPA routers in Alpine  
- Storing passwords or tokens in `Alpine.store`  
- `eval` / unsanitized HTML injection  
- Competing jQuery + Alpine on same control  
