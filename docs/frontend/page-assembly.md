# Deliverable 6 — Page Assembly Patterns

How engineers assemble pages from layouts + components. Templates: [../design/page-templates.md](../design/page-templates.md).

---

## Page lifecycle

```
Route → Middleware (auth, tenant, permission)
  → Controller (authorize, load data, presenters)
    → View (layout + components)
      → Alpine enhance
        → Optional AJAX refresh
```

Blade must not authorize as the only gate — controller/`authorize` / policies first; UI hides extras.

---

## Data flow

| Data | Source |
|------|--------|
| Nav permissions | User + TenantContext (sidebar) |
| Page records | Controller → view vars / View Models |
| Flash | Session → `<x-flash-messages>` |
| Small client config | `@js([...])` sparingly |
| Widget JSON | Dashboard API endpoints (existing) |

Avoid N+1 in controllers; eager load for listings.

---

## Composition recipes

### Workspace Home

```blade
<x-app-layout>
  <x-slot:header>… workspace title …</x-slot:header>
  <x-workspace.attention :items="$attention" />
  <x-workspace.kpi-strip :kpis="$kpis" />
  <x-workspace.quick-actions :actions="$actions" />
  <div class="grid grid-cols-12 gap-4">
    @foreach ($widgets as $widget)
      <x-widgets.frame … />
    @endforeach
  </div>
  <x-activity.feed :items="$activity" />
</x-app-layout>
```

### Dashboard (specialized)

Same as home with different widget set / fewer CTAs.

### Listing

```blade
<x-app-layout>
  <x-entity.page-header :title="…" :create-url="…" />
  <x-filters.bar>…</x-filters.bar>
  <x-tables.shell>
    … columns …
  </x-tables.shell>
  {{ $paginator->links() }}
</x-app-layout>
```

### Detail

```blade
<x-entity.detail-shell :record="$model" :tabs="$tabs">
  <x-slot:overview>… fields …</x-slot:overview>
  <x-slot:side>… meta …</x-slot:side>
</x-entity.detail-shell>
```

### Create / Edit

```blade
<form method="POST" …>
  @csrf @method
  <x-forms.section title="…">
    <x-forms.field>…</x-forms.field>
  </x-forms.section>
  <x-forms.footer cancel="…" submit="Save" />
</form>
```

### Configuration

Hub layout + section cards; sticky save.

### Reports / Profile / Settings

Follow design templates; reuse listing/detail/form shells.

---

## Component composition rules

1. Layout wraps everything.  
2. Page owns data wiring.  
3. Components own presentation.  
4. Partials only for one-off module markup.  
5. Keep modules’ `show.blade.php` readable (< ~200 lines ideal; extract partials).  

---

## Permission-aware assembly

```blade
@can / @if($user->hasPermission(...))
  <x-ui.button>Create</x-ui.button>
@endif
```

Prefer same permission strings as backend routes.

---

## Anti-patterns

- Controller returning HTML strings  
- Page copying sidebar into itself  
- Fetching on the client what the controller already could pass  
- Three different header markups across modules  
