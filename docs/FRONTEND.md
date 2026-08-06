# Konnect Nex — Frontend Documentation

Everything you need to run, build, and extend the Konnect Nex user interface.

---

## Stack overview

| Layer | Technology | Purpose |
|-------|------------|---------|
| Templates | **Blade** (Laravel) | Server-rendered HTML |
| Styling | **Tailwind CSS 3** + `@tailwindcss/forms` | Utility-first CSS |
| Interactivity | **Alpine.js 3** | Lightweight JS (sidebar, tabs, dynamic forms) |
| Bundler | **Vite 6** + `laravel-vite-plugin` | CSS/JS build & hot reload |
| Font | **Figtree** (Bunny Fonts CDN) | UI typography |
| Auth UI | **Laravel Breeze** (Blade stack) | Login, register, profile |

There is **no React/Vue SPA**. All CRM pages are Blade views with shared layouts and components.

---

## Prerequisites

- **Node.js** 18+ and **npm**
- **PHP** 8.2+ and **Composer** (backend serves the pages)
- For local dev: either `php artisan serve` **or** XAMPP/Apache

---

## Quick start (development)

```bash
# 1. Install JS dependencies (once, or after package.json changes)
npm install

# 2. Start Vite dev server (keep running in a terminal)
npm run dev

# 3. In another terminal, start Laravel
php artisan serve
```

Open **http://127.0.0.1:8000**.

With `npm run dev` running, Vite injects hot-reloaded assets. Edit files under `resources/views/`, `resources/css/`, or `resources/js/` and refresh the browser.

---

## Production / XAMPP build

After any CSS, JS, or Blade change that affects compiled assets:

```bash
npm run build
```

This writes hashed files to `public/build/` and updates `public/build/manifest.json`.

### `php artisan serve` (root install)

- App URL: `http://127.0.0.1:8000`
- Assets load from `/build/assets/…`
- No extra configuration needed

### XAMPP subdirectory (e.g. `http://localhost/nova-crm/public`)

Konnect Nex auto-detects the subdirectory via `ConfigureSubdirectory` middleware:

- Sets `app.asset_url`, session path, and public disk URL
- Vite assets use the prefixed path: `http://localhost/nova-crm/public/build/assets/…`

Ensure `.env` matches your setup:

```env
APP_URL=http://localhost/nova-crm/public
```

Run `npm run build` after pulling changes or editing frontend files.

### Storage & logos

Organization logos and attachments use the **public** disk:

```bash
php artisan storage:link
```

Without this, uploaded images may 404 in the UI.

---

## File structure

```
resources/
├── css/
│   └── app.css              # Tailwind entry + custom component classes
├── js/
│   ├── app.js               # Alpine.js bootstrap
│   └── bootstrap.js         # Axios defaults (CSRF)
└── views/
    ├── layouts/
    │   ├── app.blade.php    # Authenticated shell (sidebar + header)
    │   ├── guest.blade.php  # Auth / marketing wrapper
    │   └── sidebar.blade.php
    ├── components/          # Reusable Blade components
    ├── auth/                # Login, register, password reset
    ├── leads/               # CRM modules (same pattern for each)
    ├── customers/
    ├── pipeline/
    ├── products/
    ├── quotations/
    ├── invoices/
    ├── payments/
    ├── tasks/
    ├── reports/
    ├── team/
    ├── organizations/
    ├── profile/
    ├── audit-logs/
    ├── notifications/
    ├── search/
    ├── api-tokens/
    ├── emails/              # Markdown/HTML email templates
    ├── dashboard.blade.php
    └── welcome.blade.php    # Public marketing page

app/View/Components/
├── AppLayout.php            # Maps to layouts.app
└── GuestLayout.php          # Maps to layouts.guest

vite.config.js               # Vite inputs: app.css, app.js
tailwind.config.js           # Content paths + Figtree font
postcss.config.js
package.json
```

---

## Layouts

### Authenticated app — `<x-app-layout>`

Used for all logged-in CRM pages.

**Provides:**

- Dark **sidebar** (`layouts/sidebar.blade.php`) — org switcher, RBAC-filtered nav
- **Sticky header** — page title slot, mobile menu toggle, global search, notification bell, org badge
- **Main content area** — `p-4 sm:p-6 lg:p-8` padding
- **Alpine** `sidebarOpen` for mobile drawer

**Usage:**

```blade
<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-lg font-semibold text-slate-900">{{ __('Leads') }}</h1>
            <p class="text-sm text-slate-500">{{ __('Manage sales leads') }}</p>
        </div>
    </x-slot>

    <x-flash-messages />

    {{-- Page content --}}
</x-app-layout>
```

### Guest — `<x-guest-layout>`

Used for login, register, password flows. Centered card on `bg-slate-50`.

### Marketing — `welcome.blade.php`

Standalone page (no app layout). Dark hero, feature sections, module grid. Uses Alpine for mobile nav.

---

## Design system

Follow these conventions so new pages match the existing UI.

### Colors

| Role | Tailwind |
|------|----------|
| App background | `bg-slate-50` |
| Sidebar | `bg-slate-900`, text `text-slate-300` |
| Primary accent | `indigo-600` / `indigo-700` hover |
| Secondary accent | `violet` gradients (hero, avatars) |
| Cards | `bg-white border border-slate-200 shadow-sm rounded-xl` |
| Card header strip | `px-6 py-4 border-b bg-slate-50/50` |
| Muted text | `text-slate-500`, labels `text-slate-400` |

### Typography

- Page title: `text-lg font-semibold text-slate-900`
- Subtitle: `text-sm text-slate-500`
- Section heading inside cards: `font-semibold text-slate-900`
- Table headers: `text-xs font-semibold uppercase tracking-wide text-slate-500`

### Buttons

| Component | Use |
|-----------|-----|
| `<x-primary-button>` | Main actions (Save, Create, Submit) |
| `<x-secondary-button>` | Secondary actions |
| `<x-danger-button>` | Destructive actions |
| Inline link actions | `text-sm font-medium text-indigo-600 hover:text-indigo-800` |

Primary buttons in headers:

```blade
<a href="..." class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
```

### Status badges

Use rounded pills with semantic colors:

```blade
<span class="inline-flex text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800">
    {{ $label }}
</span>
```

Reference color maps in `leads/index.blade.php` and `tasks/index.blade.php`.

### Cards & panels

Standard content card:

```blade
<div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
        <h3 class="font-semibold text-slate-900">{{ __('Section Title') }}</h3>
        <p class="text-sm text-slate-500 mt-0.5">{{ __('Optional description') }}</p>
    </div>
    <div class="p-6">
        {{-- body --}}
    </div>
</div>
```

### Grids

- Stats row: `grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4`
- Two-column show pages: `grid grid-cols-1 xl:grid-cols-3 gap-6` (main `xl:col-span-2`)
- Filter bars: `grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3`

### Custom CSS classes (`resources/css/app.css`)

| Class | Use |
|-------|-----|
| `.sidebar-scroll` | Scrollable sidebar nav |
| `.feature-card`, `.bento-card` | Marketing page |
| `.landing-grid`, `.landing-glow`, `.text-gradient`, `.glass-nav` | Welcome page |
| `[x-cloak]` | Hide Alpine elements until initialized |

---

## Blade components

### Product-specific

| Component | Props / notes |
|-----------|----------------|
| `x-flash-messages` | Reads `session('status')` and `session('error')`. **Add new status keys here** when adding controller flash messages. |
| `x-sidebar-link` | `href`, `active`, `icon` (HTML string), optional `disabled`, `badge` |
| `x-organization-logo` | `organization`, `size`: `sm` \| `md` \| `lg` \| `xl` |
| `x-logo-upload` | Alpine preview for org logo upload |
| `x-client-email-form` | Client email with attachments; checks org SMTP config |
| `x-attachments-panel` | `attachableType`, `attachableId`, `attachments`, `canUpload`, `canDelete` |
| `x-tasks-panel` | `taskableType`, `taskableId`, `tasks`, `canCreate` |

### Laravel Breeze (forms & UI)

| Component | Use |
|-----------|-----|
| `x-input-label` | Field labels |
| `x-text-input` | Text, email, number, date, datetime-local |
| `x-input-error` | Validation errors (`:messages="$errors->get('field')")` |
| `x-primary-button` / `x-secondary-button` / `x-danger-button` | Buttons |
| `x-modal` | Confirmation dialogs (profile delete) |
| `x-dropdown` / `x-dropdown-link` | Dropdown menus |

---

## Page patterns

### Index (list + filters)

Examples: `leads/index`, `tasks/index`, `customers/index`

1. `<x-app-layout>` + header with title and optional “Add” button (`@can('create', Model::class)`)
2. `<x-flash-messages />`
3. Filter form — `method="GET"`, preserve query string on pagination
4. White card table or empty state with icon + CTA

### Create / Edit

Examples: `leads/create`, `tasks/edit`

1. Form wrapped in card with `_form` partial
2. Footer bar: Cancel link + submit button
3. `@csrf` (+ `@method('PUT')` on edit)

### Show (detail)

Examples: `leads/show`, `invoices/show`

1. Header with actions (Edit, Delete, status controls)
2. Two-column layout: main content + sidebar meta
3. Optional panels: activity/notes, attachments, tasks, client email

### Partial forms

Shared form fields live in `_form.blade.php` per module (e.g. `leads/_form.blade.php`, `tasks/_form.blade.php`).

---

## Alpine.js patterns

Alpine is loaded globally in `resources/js/app.js`. No build step beyond Vite.

### Mobile sidebar (`layouts/app.blade.php`)

```blade
<div x-data="{ sidebarOpen: false }">
    <div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak class="...overlay..."></div>
    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden ...">...</button>
</div>
```

Always use **`x-cloak`** on elements that flash before Alpine init.

### Tabbed settings (`organizations/edit.blade.php`)

```blade
<div x-data="{ tab: 'general' }">
    <button type="button" @click="tab = 'general'" :class="tab === 'general' ? '...active...' : '...'">
    <div x-show="tab === 'general'">...</div>
</div>
```

Active tab is restored from validation errors via server-side `$activeTab`.

### Dynamic line items (`quotations/_form`, `invoices/_line-items`)

Alpine manages an `items[]` array — add/remove rows, syncs with hidden inputs for POST.

### Logo preview (`components/logo-upload.blade.php`)

File input change → `URL.createObjectURL` preview; optional remove checkbox.

---

## Terminology & i18n

### Industry terminology

Use the **`crm_term()`** helper for labels that vary by organization (lead → “Applicant”, etc.):

```blade
{{ crm_term('leads') }}
{{ crm_term('customer') }}
```

Defined in `app/helpers.php`; resolved by `OrganizationTerminology` service.

### Translation

User-facing strings use Laravel's `__()` helper:

```blade
{{ __('Add Task') }}
```

---

## Permissions in views

Navigation and actions are gated by organization permissions.

### Sidebar

```blade
@php $can = fn (string $permission) => $user->hasPermission($permission, $currentOrganization); @endphp

@if ($can('leads.view'))
    <x-sidebar-link ...>{{ crm_term('leads') }}</x-sidebar-link>
@endif
```

### Actions

```blade
@can('create', App\Models\Lead::class)
    <a href="{{ route('leads.create') }}">...</a>
@endcan

@can('update', $lead)
    ...
@endcan
```

Policies map to RBAC permissions in `config/rbac.php`.

---

## Flash messages

Controllers redirect with:

```php
return redirect()->route('leads.show', $lead)->with('status', 'lead-created');
```

Register the message in `resources/views/components/flash-messages.blade.php`:

```php
'lead-created' => __('Lead created successfully.'),
```

For errors:

```php
return back()->with('error', __('Something went wrong.'));
```

---

## Forms checklist

| Requirement | How |
|-------------|-----|
| CSRF | `@csrf` on every POST/PUT/PATCH/DELETE form |
| Method spoofing | `@method('PUT')` / `@method('PATCH')` / `@method('DELETE')` |
| Validation errors | `<x-input-error :messages="$errors->get('field')" />` |
| Old input | `:value="old('name', $model->name)"` |
| File uploads | `enctype="multipart/form-data"` on form |
| Selects | `border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm` |
| Permission on submit | Gate in FormRequest or `@can` wrapping the form |

---

## Module page map

| Module | Views | Layout notes |
|--------|-------|--------------|
| Dashboard | `dashboard.blade.php` | Stats cards, recent leads, upcoming tasks |
| Leads | `leads/*` | Filters, status badges, notes, attachments, tasks |
| Customers | `customers/*` | Same pattern as leads |
| Pipeline | `pipeline/*` | Kanban-style index, stage updates on show |
| Products | `products/*` | SKU, pricing |
| Quotations | `quotations/*` | Alpine line items, send email, attachments |
| Invoices | `invoices/*` | Line items, payments panel, send email |
| Payments | `payments/*` | Record payment, receipt email |
| Tasks | `tasks/*` | Due dates, complete action, link to records |
| Reports | `reports/index` | Analytics cards |
| Team | `team/index` | Member list, role assignment |
| Org settings | `organizations/edit` | Tabbed: General, Brand, Address, Preferences, Terminology, Email, Roles |
| Org setup | `organizations/setup` | Onboarding after register |
| Audit log | `audit-logs/index` | Read-only activity table |
| Notifications | `notifications/index` | Mark read / read all |
| Search | `search/index` | Cross-module results |
| API tokens | `api-tokens/index` | Create/revoke Sanctum tokens |
| Profile | `profile/edit` | Breeze partials |
| Auth | `auth/*` | Guest layout |
| Welcome | `welcome.blade.php` | Public marketing |

---

## Email templates (frontend-related)

Under `resources/views/emails/` — Blade templates for outbound mail:

- `quotations/sent`, `invoices/sent`, `payments/receipt`, `customers/message`
- `organizations/test` — SMTP test email

These are HTML emails, not app pages, but follow similar Tailwind-friendly inline styles where needed.

---

## Adding a new CRM module (UI checklist)

1. **Routes** — `routes/web.php` resource routes inside `auth` + `ensure.organization` middleware
2. **Controller** — return Blade views; flash status keys
3. **Policy** — register in `AppServiceProvider`
4. **Views** — `index`, `create`, `edit`, `show`, optional `_form.blade.php`
5. **Sidebar** — add link in `layouts/sidebar.blade.php` with `$can('module.view')`
6. **RBAC** — permissions in `config/rbac.php` + migration to seed
7. **Flash messages** — add keys to `flash-messages.blade.php`
8. **Dashboard** — optional stat card / module tile in `dashboard.blade.php`
9. **Terminology** — use `crm_term()` for user-facing module names where applicable
10. **Build** — run `npm run build` before deploying or testing without `npm run dev`

---

## Vite configuration

```js
// vite.config.js
laravel({
    input: ['resources/css/app.css', 'resources/js/app.js'],
    refresh: true,  // full page reload when Blade/PHP changes during npm run dev
}),
```

**Do not add a second CSS entry** unless you also register it in the layout's `@vite([...])` directive.

### Tailwind content paths

`tailwind.config.js` scans:

- `resources/views/**/*.blade.php`
- Laravel pagination views
- Compiled views in `storage/framework/views/`

If a new template path is added outside these globs, extend the `content` array.

---

## Troubleshooting

### Styles missing / unstyled page

1. Run `npm run dev` **or** `npm run build`
2. Confirm `@vite(['resources/css/app.css', 'resources/js/app.js'])` is in the layout `<head>`
3. On XAMPP: verify `APP_URL` and check page source for correct `/build/assets/` prefix

### Alpine not working (sidebar, tabs)

1. Ensure `npm run dev` or built JS is loaded
2. Check browser console for JS errors
3. Use `x-cloak` on `x-show` elements to avoid flash

### Logo / attachment images 404

```bash
php artisan storage:link
```

Verify `public/storage` symlink exists.

### CSRF token mismatch on AJAX

`resources/js/bootstrap.js` sets Axios CSRF from `<meta name="csrf-token">` in layouts. For fetch/Alpine POSTs, include `@csrf` in forms instead.

### Pagination styling broken

Tailwind must include vendor pagination views (already in `tailwind.config.js`).

---

## Commands reference

| Command | When |
|---------|------|
| `npm install` | First setup, or after dependency changes |
| `npm run dev` | Local development with hot reload |
| `npm run build` | Production / XAMPP / before deploy |
| `php artisan serve` | Local Laravel server |
| `php artisan storage:link` | Enable public file URLs |
| `php artisan test` | Includes `AssetUrlTest` for build path checks |

---

## Related docs

- [NEXT_PHASE_PROMPT.md](./NEXT_PHASE_PROMPT.md) — Backend phases, RBAC, and feature roadmap
- [Laravel Vite docs](https://laravel.com/docs/vite)
- [Tailwind CSS docs](https://tailwindcss.com/docs)
- [Alpine.js docs](https://alpinejs.dev/)
