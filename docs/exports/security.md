# Export Security

## Controls

1. **RBAC** — `exports.view|create|manage` plus module scopes (`exports.crm`, …)
2. **Module licensing** — catalog hides unlicensed modules
3. **Organization isolation** — sessions, files, and downloads are org-scoped
4. **Field protection** — adapter `sensitive: true` columns are never selectable
5. **Download TTL** — signed token + expiry; revoke deletes the file

## Sensitive fields

Adapters must mark secrets as sensitive (example: user `password`). Hidden columns are available for advanced presets later but are omitted from the default create UI.
