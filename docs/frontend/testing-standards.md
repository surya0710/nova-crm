# Deliverable 11 — UI Testing Standards

Frontend QA expectations for NovaCRM.

---

## Test types

| Type | Goal | Tools / approach |
|------|------|------------------|
| Visual | Layout regressions | Manual checklist; optional Percy/Chromatic later |
| Responsive | Breakpoints | Browser DevTools + real device spot checks |
| Accessibility | AA / keyboard | axe DevTools, keyboard-only pass |
| Keyboard | Operability | Documented critical paths |
| Cross-browser | Compatibility | Chromium + Firefox + Safari (or WebKit) |
| Regression | Feature unbroken | Laravel Dusk (if adopted) / feature tests for HTML presence |
| Performance | Budgets | Lighthouse CI optional; build size review |

Backend Feature tests remain primary for permissions/HTTP; UI tests complement.

---

## Visual testing

For redesigned templates:

- [ ] Workspace home  
- [ ] Listing + empty  
- [ ] Detail tabs  
- [ ] Modal open  
- [ ] Dashboard widgets loading/error  

Capture before/after screenshots in PR when changing shell/nav.

---

## Responsive testing

Widths: **360 · 768 · 1280 · 1920**

Critical flows on 360: login, home, list, open record, create, approve (if role).

---

## Accessibility testing

- axe: **0 critical** on changed pages  
- Manual: skip link, focus rings, modal trap  
- Color contrast sample on primary buttons + sidebar  

---

## Keyboard testing scripts

Minimum paths:

1. Open mobile nav → navigate → close Esc  
2. Open modal → tab cycle → Esc → focus restored  
3. Dropdown menu operable  
4. Form: tab through fields → submit  

---

## Cross-browser

| Browser | Priority |
|---------|----------|
| Latest Chrome/Edge | P0 |
| Latest Firefox | P1 |
| Safari 16+ | P1 |

No IE support.

---

## Regression testing

- Prefer Feature tests asserting status 200 + see text for critical pages  
- Dusk/browser tests for Alpine-heavy chrome when flakiness is manageable  
- Smoke after `npm run build` in CI when frontend changes  

---

## Performance validation

- Lighthouse smoke on Home (staging) for major UX releases  
- Widget waterfall: no single widget blocks shell  
- Bundle diff on `app.js` / `app.css`  

---

## PR evidence

UI PRs should include:

- Checklist ([code-review-checklist.md](./code-review-checklist.md))  
- Notes on breakpoints tested  
- axe summary if interactive chrome changed  

---

## Anti-patterns

- “Works on my machine” at one zoom only  
- Screenshot-only QA without keyboard  
- Skipping empty/error states  
