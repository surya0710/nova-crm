# Deliverable 5 — Component Library

Reusable UI components for NovaCRM. Documented as a **pattern catalog** for Phase 14 Blade/`x-` implementation (not built in this phase).

---

## Conventions

| Rule | Spec |
|------|------|
| API | Variants via props: `variant`, `size`, `state` |
| Icons | Left of label by default; `icon-md` in buttons |
| Disabled | `opacity-disabled` + `aria-disabled` / disabled |
| Loading | Spinner replaces icon; keep label |
| Glossary | Labels use [product-glossary](../product/product-glossary.md) |

---

## Actions

### Button

| Variant | Use |
|---------|-----|
| `primary` | One primary action per region |
| `secondary` | Secondary |
| `ghost` | Tertiary / toolbar |
| `danger` | Destructive |
| `link` | Inline text action |

Sizes: `sm` · `md` (default) · `lg`.  
Icon-only: require `aria-label`.

### Button group / split button

Primary + overflow chevron for alternate creates.

### Quick Action

See [../product/quick-actions.md](../product/quick-actions.md) — compact button with icon+label on home bars.

---

## Feedback & status

### Badge

Soft + solid styles; semantic colors; optional dot; not for long text.

### Alert / Banner

Info · success · warning · danger; dismissible optional; icon required.

### Toast

Transient; bottom/top-right; auto-dismiss P2; sticky for errors until dismissed.

### Progress bar

Determinate preferred; label with %.

### Spinner / Skeleton

Skeleton for content regions; spinner for buttons/inline.

---

## Data display

### Card

Surface for grouping; title slot; actions overflow; **not** default wrapper for every block.

### Avatar

Initials or image; sizes per tokens; status ring optional.

### KPI

Value + label + optional delta.

### Health indicator

Text + color + icon.

### Empty state

Illustration optional; title; body; primary CTA ([../product/empty-states.md](../product/empty-states.md)).

### Activity item

Avatar · text · time · link.

### Comment

Thread; composer; mention support.

### Timeline card

Date grouping; event rows.

### File upload

Dropzone + file list; progress; type/size errors.

### Charts

Wrapper with title, legend, empty, table alternative.

### Calendar

Month/week; event chips; a11y grid.

### Kanban card

Title, meta, assignee avatar, labels; drag handle.

---

## Tables & lists

### Table

See [table-standards.md](./table-standards.md).

### Pagination

Prev/next + page size; item count.

### List row

Mobile substitute for tables when needed.

---

## Forms

### Input, Textarea, Select, Combobox

### Checkbox, Radio, Switch

### Date / DateTime picker

### File input

### Field / FieldGroup

Label, control, help, error slots.

### Metadata field renderer

Dynamic fields from metadata platform.

Full rules: [form-standards.md](./form-standards.md).

---

## Navigation & overlays

### Sidebar / SidebarLink

Existing pattern formalized — [navigation-components.md](./navigation-components.md).

### Tabs

Underline or enclosed; ARIA tablist.

### Accordion

FAQ / progressive sections; one open optional.

### Breadcrumbs

### Dropdown / Menu

### Tooltip

Delay 300–500ms; keyboard accessible.

### Popover

### Modal / Dialog

### Drawer

### Command palette

### Search bar

Header + results page.

### Context bar

Cross-workspace return chip.

### Favorites / Recents lists

---

## Filters

### Filter bar

Search + chips + “More filters”.

### Filter chip

Removable.

### Saved filter select

---

## Dashboard

### Widget frame

### Attention rail

### Quick Actions bar

See [dashboard-standards.md](./dashboard-standards.md).

---

## Component inventory checklist

| Component | Documented |
|-----------|------------|
| Buttons | Yes |
| Cards | Yes |
| Badges | Yes |
| Avatars | Yes |
| Tables | Yes (detail doc) |
| Forms / inputs / selects / checkbox / radio | Yes |
| Date pickers | Yes |
| Tabs / Accordions / Breadcrumbs | Yes |
| Pagination / Filters / Search | Yes |
| Drawers / Modals / Tooltips / Dropdowns | Yes |
| Progress / Kanban / Timeline | Yes |
| Charts / Calendars / Uploads | Yes |
| Comments / Activity / Widgets / Quick Actions | Yes |

---

## Implementation notes

- Prefer extending existing Blade components (`sidebar-link`, form controls) before inventing duplicates.  
- Alpine for open/close state.  
- No React component package required for Phase 14 MVP.  
