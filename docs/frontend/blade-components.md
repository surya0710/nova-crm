# Deliverable 2 — Blade Component Standards

Standards for Blade components in Konnect Nex.

---

## Naming conventions

| Kind | Pattern | Example |
|------|---------|---------|
| Anonymous component file | `kebab-case.blade.php` | `sidebar-link.blade.php` |
| Tag | `<x-{path}>` with `.` for nested dirs | `<x-nav.sidebar-link>` |
| Class component | StudlyCase PHP + matching view | `AppLayout` → `layouts.app` |
| Slot names | camelCase or kebab in slot attrs | `header`, `footer`, `actions` |
| Props | camelCase in PHP / `@props` | `:href`, `variant` |

Nested directories map to dots: `components/ui/button/index.blade.php` → `<x-ui.button>` (or `button.blade.php` → `<x-ui.button>`).

---

## Directory structure

See [folder-architecture.md](./folder-architecture.md). Primitives under `ui/` and `forms/`; composites under `domain/`.

---

## Anonymous vs class components

| Prefer anonymous when | Prefer class when |
|-----------------------|-------------------|
| Markup + light `@props` | Non-trivial PHP (queries forbidden — compute in class carefully) |
| Design-system primitives | Layouts (`AppLayout`, `GuestLayout`) |
| No constructor logic | Need `Illuminate\View\Component` lifecycle |

**Do not** put authorization-heavy queries inside components without caching — prefer controllers/view composers passing data.

---

## Props

```blade
@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
])
```

Rules:

- Declare defaults in `@props`  
- Boolean props: pass `:disabled="true"`  
- Merge attributes: `{{ $attributes->class([...]) }}` / `->merge([...])`  
- Never accept raw unsanitized HTML in props without `{!! !!}` policy review  

---

## Variants & sizes

Align with design system:

- Buttons: `primary` | `secondary` | `ghost` | `danger` | `link`  
- Sizes: `sm` | `md` | `lg`  
- Map variants to Tailwind classes in one place (component), not at every callsite  

---

## Slots & composition

| Slot | Use |
|------|-----|
| Default | Main body |
| Named | `header`, `footer`, `actions`, `icon` |
| Attribute bag | Classes/ARIA on root |

Compose pages from layout → template regions → components — not one monolithic Blade file when sections repeat.

---

## Reusable patterns

| Pattern | Component direction |
|---------|---------------------|
| Page header | `<x-ui.page-header>` title, subtitle, actions |
| Flash | `<x-flash-messages>` / toast region |
| Modal | Extend existing `<x-modal>` |
| Form field | `<x-forms.field>` wrapping label/input/error |
| Sidebar link | `<x-nav.sidebar-link>` (evolve current) |
| Empty state | `<x-ui.empty-state>` |

---

## Workspace shell

Target composition:

```blade
<x-app-layout :workspace="$workspace">
    <x-slot:header>…</x-slot:header>
    <x-workspace.context-bar … />
    {{ $slot }}
</x-app-layout>
```

Workspace id/name from backend; nav data not hard-coded in every page.

---

## Entity layouts

Shared partials/components for listing/detail:

- `x-entity.listing-shell` — filters + table slot + pagination  
- `x-entity.detail-shell` — breadcrumbs + identity header + tabs + columns  

Module pages fill slots with domain fields.

---

## Existing components to preserve/evolve

| Current | Action |
|---------|--------|
| `primary-button`, `secondary-button`, `danger-button` | Unify under `ui.button` with variant (alias old tags during migration) |
| `text-input`, `input-label`, `input-error` | Nest under `forms.*` |
| `modal`, `dropdown` | Move under `ui.*` |
| `sidebar-link` | Move under `nav.*` |
| Domain modals (`lead-convert-modal`, …) | Keep under `domain/` |

---

## Anti-patterns

- 500-line page with duplicated button markup  
- Components that redirect or write to DB  
- Inline `<style>` in components  
- Breaking attribute merge (`class` overwritten)  
