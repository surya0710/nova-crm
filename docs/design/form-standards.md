# Deliverable 9 — Form Standards

Standards for create/edit/configuration forms.

---

## Principles

1. Labels always visible (not placeholder-only).  
2. One primary submit.  
3. Errors are specific and adjacent.  
4. Group related fields; progressive disclosure for advanced.  
5. Metadata fields use the same field chrome as native fields.

---

## Field grouping

- Logical sections with `h2`/`h3` + short description  
- Section cards optional — prefer whitespace over card-per-field  
- Danger zone: isolated last section  

---

## Labels

- `text-sm font-medium`  
- Associate with `for` / `aria-labelledby`  
- Optional fields unmarked; required use indicator  

---

## Required indicators

- Asterisk `*` with legend “Required” once per form  
- `aria-required="true"`  
- Do not mark every optional as “(optional)” unless dense forms need it  

---

## Validation

| Timing | Rule |
|--------|------|
| Blur | Format / required for touched fields |
| Submit | Full form |
| Server | Map to fields; residual to alert |

Error text: text-sm danger; icon optional.

---

## Inline help

- Help under field (`text-xs` muted)  
- Tooltips for long policy explanations  
- Links to Knowledge where useful  

---

## Autosave

- Edit existing records only (when enabled)  
- Status in header  
- Create flows: explicit submit  

---

## Multi-step forms

- Stepper: numbered; allow return to prior steps  
- Validate step before next  
- Summary step before final commit for high-impact (hire, payroll run)  

---

## Section cards

Use when settings pages need clear separation; each card one concern; save per page or sticky save.

---

## Attachments

- Upload component in its own section  
- Show constraints (types, max size) before select  
- Existing files list with remove  

---

## Metadata fields

- Render inside forms via layout definitions  
- Same label/error/help slots  
- Respect field-level permissions (hide vs read-only)  
- Do not break native field rhythm  

---

## Responsive layouts

| Viewport | Layout |
|----------|--------|
| Mobile | Single column; full-width controls |
| Desktop | Single column default; 2-col for paired fields |
| Sticky footer | Actions remain reachable |

Touch targets ≥ 44px.

---

## Control specs (summary)

| Control | Notes |
|---------|-------|
| Text | spellcheck as appropriate |
| Textarea | resize vertical; character count if limited |
| Select | native or accessible listbox |
| Combobox | typeahead for large lists |
| Checkbox / Radio | target includes label |
| Switch | immediate effect only when safe; else Save |
| Date | locale-aware; keyboard entry |

---

## Accessibility

- Visible focus  
- Errors referenced with `aria-describedby`  
- Do not rely on color alone for invalid  
- Maintain focus on first error after submit  

---

## Anti-patterns

- Placeholder-as-label  
- Clearing the form on minor validation errors  
- Multi-primary buttons competing  
- Horizontal field sprawl on mobile  
