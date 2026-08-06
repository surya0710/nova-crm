# Entity Picker

Reusable Blade component for searchable entity selection.

## Usage

```blade
<x-forms.entity-picker
    entity="users"
    name="owner_id"
    :value="old('owner_id', $lead->assigned_to)"
    :required="true"
    placeholder="Search employee…"
/>
```

## Props

| Prop | Description |
|------|-------------|
| `entity` | Lookup entity key (`users`, `employees`, etc.) |
| `name` | Form input name (optional; emits hidden input) |
| `value` | Pre-selected ID |
| `required` | HTML required attribute |
| `placeholder` | Search input placeholder |
| `endpoint` | Override lookup URL (optional) |

## Features

- Typeahead search with debounce
- Keyboard navigation (↑ ↓ Enter Esc)
- Infinite scroll / pagination
- Selected item display with badge
- Clear selection
- Loading and empty states

## Bulk toolbar integration

Bulk actions declare lookup field types in `inputFields()`:

```php
[
    'key' => 'owner_id',
    'label' => 'Assign Owner',
    'type' => 'user',
    'required' => true,
]
```

Supported types: `user`, `employee`, `department`, `designation`, `branch`, `shift`, `lookup` (with `lookup` key for custom entity).

The bulk toolbar renders `<x-forms.entity-picker>` behavior automatically.

## JavaScript API

The `entityPicker(config)` Alpine factory is available globally via `components/forms/partials/entity-picker-script.blade.php`.

For custom Alpine integrations:

```javascript
x-data="entityPicker({
    endpoint: '/shell/lookups/users',
    parent: formData,
    fieldKey: 'owner_id',
})"
```
