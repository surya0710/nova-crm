# Deliverable 13 — Motion & Animation

Motion principles for NovaCRM. Motion supports hierarchy and feedback — not decoration.

---

## Principles

1. **Purposeful** — explain state change.  
2. **Fast** — prefer ≤ 300ms for UI chrome.  
3. **Interruptible** — Esc/cancel wins.  
4. **Reduced motion** — honor `prefers-reduced-motion: reduce`.  
5. **Performance** — transform/opacity only when possible; avoid layout thrash.

---

## Timing tokens

| Token | Ms | Use |
|-------|-----|-----|
| fast | 100–150 | Hover, color, focus |
| normal | 200 | Tabs, accordions, menu |
| moderate | 300 | Drawer, modal enter |
| slow | 400–500 | Rare page-level |

Easings: standard ease-out enter; ease-in exit.

---

## Page transitions

- Default: **none** or subtle fade 150ms  
- No sliding full-page between modules (feels heavy)  
- Preserve scroll on back when possible  

---

## Drawer animation

- Enter: translateX + fade, 300ms  
- Exit: faster 200ms  
- Backdrop fade parallel  
- Reduced motion: fade only or instant  

---

## Modal animation

- Enter: fade + slight scale 0.98→1 (optional) 200–300ms  
- Exit: fade 150ms  
- Reduced motion: fade/instant  

---

## Loading animation

- Skeleton pulse 1.5s cycle or static  
- Spinners: CSS rotate; pause under reduced motion (show static indicator)  

---

## Hover animation

- Color/border ≤ 150ms  
- No bounce, no large translate  
- No glow shadows  

---

## Expand / collapse

- Height animation carefully (prefer grid `0fr`/`1fr` or Alpine)  
- Accordion 200ms  
- Announce expanded state  

---

## Drag feedback

- Dragged item: opacity 0.9 + shadow-md  
- Placeholder gap  
- Drop highlight border primary  
- Reduced motion: opacity only  

---

## Notification animation

- Toast slide/fade 200ms  
- Stack with gap  
- Do not block input focus unexpectedly  

---

## Performance limits

| Rule | Limit |
|------|-------|
| Concurrent animated widgets | Prefer ≤ 3 pulsing skeletons |
| Parallax / continuous loops in-app | Forbidden |
| Large blur animations | Avoid |
| Chart enter | Optional once; no repeat |

---

## Recommended intentional motions (product)

Per workspace experience: (1) drawer/modal enter, (2) toast feedback, (3) accordion/nav expand — enough presence without noise.

---

## Anti-patterns

- Decorative infinite gradients animating  
- Staggered 20-item list fly-ins on every dashboard load  
- Motion that delays time-to-interactive  
