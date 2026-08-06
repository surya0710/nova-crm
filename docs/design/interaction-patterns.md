# Deliverable 7 — Interaction Patterns

Shared interaction behavior across Konnect Nex.

---

## Loading

| Context | Pattern |
|---------|---------|
| First paint page | Route-level progress optional; prefer content skeletons |
| Dashboard widgets | Per-widget skeleton |
| Tables | Header visible + row skeletons |
| Buttons | Spinner + disabled duplicate submit |
| Infinite scroll | Bottom spinner |

Never blank the entire shell.

---

## Skeletons

- Match final layout shape  
- Neutral pulse; respect `prefers-reduced-motion` (static)  
- No fake text that looks real  

---

## Progress indicators

- Determinate for uploads, imports, long jobs  
- Show % or step “2 of 5”  
- Allow navigate away with toast “Running in background” when safe  

---

## Success messages

- Toast for lightweight saves  
- Inline banner for important confirmations  
- Copy: past tense (“Lead created”) + optional View link  

---

## Error messages

| Level | UI |
|-------|-----|
| Field | Under input |
| Form | Alert at top + focus first error |
| Page/widget | Inline error + Retry |
| Global | Toast / banner |

Copy: human, actionable; include reference id for unexpected 500s.

---

## Validation

- Validate on blur for most fields; on submit for all  
- Required marked before submit  
- Async uniqueness: debounce + spinner  
- Server errors map to fields when possible  

---

## Confirmations

| Risk | Pattern |
|------|---------|
| Safe | No confirm |
| Reversible | Soft confirm optional |
| Destructive | Modal: title, consequence, Cancel · Delete |
| Bulk destructive | Type-to-confirm when high impact |

---

## Undo

- Prefer undo toast (5–10s) for archive/delete soft  
- Hard delete may not undo  
- Announce to SR  

---

## Bulk actions

1. Select rows → bulk bar appears  
2. Choose action → confirm if needed  
3. Progress for large N  
4. Summary toast (N succeeded, M failed)  

---

## Autosave

- Indicator: Saving… · Saved · Error  
- Debounce 500–1000ms  
- Conflict: reload prompt  
- Not for create-first pages until record exists  

---

## Notifications

Bell opens panel; patterns in [../product/workspace-notifications.md](../product/workspace-notifications.md).

---

## Drag & drop

- Visible handle  
- Drop target highlight  
- Keyboard alternative mandatory  
- Cancel on Esc  
- Live region announce move  

---

## Hover

- Desktop affordance only; never sole way to reveal critical actions  
- Row hover reveals secondary actions; keep ⋯ always visible on touch  

---

## Focus

- Visible ring (primary)  
- Focus trap in modal/drawer/palette  
- Restore focus on close  

---

## Keyboard shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl/⌘ K` | Command palette |
| `Esc` | Close overlay |
| `?` | Shortcut help (future) |

Document in Knowledge; avoid hijacking browser shortcuts.

---

## Permission denial mid-flow

If action fails 403: toast + remain on page; do not soft-navigate to raw error.

---

## Anti-patterns

- Multiple stacked modals  
- Blocking alerts for ordinary success  
- Hover-only destructive actions  
- Infinite spinners without timeout messaging  
