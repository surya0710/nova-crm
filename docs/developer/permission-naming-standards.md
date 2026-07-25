# Permission Naming Standards

## Format

```
{module}.{resource}.{action}
```

Examples:

- `leads.view`
- `projects.create`
- `recruitment.offer.approve`
- `rbac.roles.manage`

## Standard Actions

| Action | Description |
|--------|-------------|
| view | Read access |
| create | Create records |
| edit | Update records |
| delete | Remove records |
| restore | Restore soft-deleted records |
| archive | Archive records |
| approve | Approval workflows |
| reject | Rejection workflows |
| export | Export data |
| import | Import data |
| assign | Assign records to users |
| manage | Full module management |
| configure | Configuration access |

## Modules

Modules map to permission groups via `config/dynamic_rbac.php` (`module_group_map`).

## System vs Organization Permissions

- **System permissions** (`organization_id = null`) — platform catalog
- **Organization permissions** — cloned on provisioning; may be customized per tenant

Legacy permissions from `config/rbac.php` remain valid and are mapped to groups automatically.
