# Deliverable 12 — Iconography & Illustrations

Visual asset rules for NovaCRM.

---

## Icon library

| Decision | Spec |
|----------|------|
| **Primary set** | Outline Heroicons-compatible (matches `heroicon-o-*` in dashboard config + sidebar stroke SVGs) |
| **Style** | Outline, 24px artboard, stroke **1.5** |
| **Solid** | Sparingly for selected/toggle states |
| **Custom** | Only when set lacks a concept; match stroke |

Do not mix filled Material icons randomly with outline Heroicons.

---

## Icon sizes

| Token | Px | Use |
|-------|-----|-----|
| xs | 12 | Dense |
| sm | 16 | Input, badge |
| md | 20 | Nav, button |
| lg | 24 | Empty/feature |
| xl | 32 | Rare |

Align optically in buttons (optical center).

---

## Usage rules

1. Decorative icons: `aria-hidden="true"`.  
2. Icon-only controls: accessible name required.  
3. Color inherits text color; status icons use semantic color.  
4. Never rely on icon alone for status.  
5. Keep metaphor consistent (plus = create, trash = delete).  

---

## Status icons

| Meaning | Metaphor |
|---------|----------|
| Success | Check circle |
| Warning | Exclamation triangle |
| Danger | X circle / octagon |
| Info | Information circle |
| Health | Heart / pulse / status dot+label |

---

## Avatar system

- Image if present; else initials (2 letters) on primary/neutral chip  
- Rounded full  
- Optional presence later — not required  
- Group: stack max 3 +N  

---

## File icons

- Generic file + extension label  
- Optional MIME-specific (PDF, image, sheet) using consistent set  
- Never execute/preview unsafe types inline without controls  

---

## Illustrations

| Use | Guidance |
|-----|----------|
| Empty states | Simple line/spot; one accent; no noisy scenes |
| Onboarding | Optional; skippable |
| Errors | Calm; not blameful cartoon |
| Marketing landing | May be richer; separate from app chrome |

Prefer monochrome + primary accent. Avoid stock 3D purple blobs.

---

## Empty state graphics

- Max width ~160–240px  
- Optional; text+CTA sufficient  
- Match theme (light/dark)  

---

## Brand logos

- Org logo in sidebar via existing organization logo component  
- Fallback: initials  
- Clear space: padding `space-2`+  
- Dark sidebar: prefer light/wordmark variants when provided  

---

## Anti-patterns

- Emoji as UI icons in enterprise chrome  
- Different stroke widths in one toolbar  
- Low-contrast icons on slate-900 without enough brightness  
