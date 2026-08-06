# Permission Templates

## Available Templates

| Slug | Name | Use Case |
|------|------|----------|
| corporate | Corporate | Full enterprise hierarchy (default) |
| startup | Startup | Minimal management layers |
| agency | Agency | Project-focused teams |
| healthcare | Healthcare | Department-based structure |
| education | Education | Academic role structure |

## Operations

- **Preview** — inspect roles and permission slugs before install
- **Install** — apply template roles and permissions to an organization
- **Reset** — remove custom roles and reinstall the default template
- **Clone** — duplicate a template for customization (API/service)

## Default Template

The Corporate template is marked `is_default = true` and is applied automatically during organization provisioning.

## Template Structure

```
permission_templates
  └── permission_template_roles
        └── permission_template_permissions (permission slugs)
```

Permission slugs reference the global permission catalog. A slug of `*` grants all permissions to that template role.
