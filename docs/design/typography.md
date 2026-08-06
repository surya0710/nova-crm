# Deliverable 3 — Typography System

Typography for NovaCRM in-app UI.

---

## Font families

| Role | Stack | Source today |
|------|-------|--------------|
| **Sans (UI)** | Figtree, ui-sans-serif, system-ui, sans-serif | Bunny Fonts / `tailwind.config.js` |
| **Mono** | ui-monospace, SFMono-Regular, Menlo, Consolas, monospace | System |
| **Display** | Not used in-app | Marketing landing may differ |

Do not introduce Inter/Roboto as a second UI font. One product sans: **Figtree**.

---

## Type scale

| Token | Size | Px | Weight default | Use |
|-------|------|-----|----------------|-----|
| `text-xs` | 0.75rem | 12 | medium | Badges, captions, sidebar section labels |
| `text-sm` | 0.875rem | 14 | normal | Body secondary, table cells, form help |
| `text-base` | 1rem | 16 | normal | Body primary, inputs |
| `text-lg` | 1.125rem | 18 | semibold | Card titles |
| `text-xl` | 1.25rem | 20 | semibold | Page section titles |
| `text-2xl` | 1.5rem | 24 | semibold | Page titles |
| `text-3xl` | 1.875rem | 30 | semibold | Rare; dashboard hero metric only |

App shell rarely exceeds **`text-2xl`** for titles.

---

## Heading scale

| Element | Class intent | Notes |
|---------|--------------|-------|
| Page title (`h1`) | text-2xl semibold | One per page |
| Section (`h2`) | text-xl semibold | |
| Subsection (`h3`) | text-lg semibold | |
| Card / widget title | text-sm–base semibold | Not oversized |
| Sidebar section | text-xs semibold uppercase tracking-wider | Matches current sidebar |

---

## Body, labels, captions

| Role | Spec |
|------|------|
| **Body** | text-sm or text-base, leading-normal, neutral-700 |
| **Label** | text-sm font-medium, neutral-700 |
| **Caption / help** | text-xs text-neutral-500 |
| **Overline** | text-xs uppercase tracking-wider text-neutral-500 |

---

## Weights

| Token | Value | Use |
|-------|-------|-----|
| normal | 400 | Body |
| medium | 500 | Labels, nav |
| semibold | 600 | Headings, buttons |
| bold | 700 | Rare emphasis / KPI |

Avoid black (900) for long text.

---

## Line heights

| Token | Value | Use |
|-------|-------|-----|
| tight | 1.25 | Headings, KPIs |
| snug | 1.375 | Card titles |
| normal | 1.5 | Body, forms |
| relaxed | 1.625 | Long help, Knowledge |

---

## Context recipes

### Navigation

- Sidebar item: text-sm medium  
- Section label: text-xs semibold uppercase tracking-wider slate-500  
- Active: semibold + primary/white on dark  

### Tables

- Header: text-xs semibold uppercase tracking-wide neutral-500  
- Cell: text-sm  
- Numeric: tabular-nums  

### Forms

- Label: text-sm medium  
- Input: text-sm or base  
- Error: text-sm danger  
- Help: text-xs muted  

### Cards / widgets

- Title: text-sm semibold  
- KPI value: text-2xl–3xl semibold tabular-nums  
- KPI delta: text-xs medium  

### Dashboard metrics

- Value dominant; label caption above or below  
- Currency/locale formatting per org  

### Code / IDs

- Mono text-xs–sm; invoice numbers, project codes  

### Knowledge / long-form

- Slightly larger body OK (base); max measure ~65–75ch  

---

## Truncation

- Single-line truncate with title tooltip for full value  
- Entity names in headers: truncate mid with ellipsis  

---

## i18n

- Prefer relative units  
- Allow +20% length in buttons for translation  
- Avoid fixed-height text boxes that clip  

---

## Anti-patterns

- Multiple display fonts in-app  
- All-caps body paragraphs  
- Centered long form text  
- KPI text as images  
- Decorative gradients on body copy  
