# Deliverable 7 — Performance Standards

Frontend performance requirements for NovaCRM.

---

## Targets (align with product metrics)

| Metric | Target |
|--------|--------|
| Workspace home primary content | ≤ 2.5s p75 LCP-like |
| Cached widget meaningful paint | ≤ 1s p75 |
| Command palette interactive | ≤ 100ms open |
| Vite JS entry | Keep lean; feature code split |

See [../product/dashboard-metrics.md](../product/dashboard-metrics.md).

---

## Lazy loading

| Asset | Strategy |
|-------|----------|
| Feature JS (workflow, gantt helpers) | Dynamic `import()` on page need |
| Charts library | Only on dashboard/report pages |
| Images below fold | `loading="lazy"` |
| Heavy modals content | Load on open when large |

---

## Deferred rendering

- Below-fold widgets: skeleton first; request on idle/intersect  
- Activity feeds: paginate  
- Do not block shell on slow widget  

---

## Image optimization

- Prefer SVG for UI/empty states  
- Uploaded logos: constrain dimensions server-side; serve reasonable sizes  
- `storage:link` required for public disk  
- No multi‑MB heroes in authenticated app  

---

## Icon strategy

- Inline SVG or shared `<x-ui.icon name="…">`  
- No icon font CSS blocking  
- Reuse paths; avoid duplicating huge SVG blobs per row — use CSS/sprite or component  

---

## Asset loading

- Vite `app.css` + `app.js` on app layout  
- `@vite` in layouts only  
- Production: `npm run build`; hashed `public/build`  
- Subdirectory installs: respect `ConfigureSubdirectory` / `APP_URL` ([../FRONTEND.md](../FRONTEND.md))  

---

## Caching

| Layer | Approach |
|-------|----------|
| Dashboard aggregates | Server cache TTL (`DASHBOARD_CACHE_TTL`) |
| Browser static | Long cache hashed build assets |
| HTML pages | Normal session pages; avoid caching personalized HTML at CDN without vary |

---

## Bundle sizing

- Review `npm run build` output sizes on major PRs  
- Reject unexplained >20% JS growth without justification  
- Prefer Alpine over adding jQuery/React  

---

## Rendering performance

- Limit Alpine `x-data` density on large tables  
- Paginate listings (15–25)  
- Avoid huge `@foreach` with nested components doing queries  
- `wire:ignore`-style issues N/A unless Livewire adopted  

---

## Dashboard loading

1. Shell + KPI counts (fast endpoints)  
2. Widgets parallel fetch with skeletons  
3. Soft refresh intervals per [../product/dashboard-blueprint.md](../product/dashboard-blueprint.md)  
4. Fail one widget ≠ fail page  

---

## Anti-patterns

- Synchronous third-party scripts in `<head>` without defer  
- Unbounded `SELECT *` painted into HTML  
- Animating layout properties on scroll  
- Importing entire chart libs globally in `app.js`  
