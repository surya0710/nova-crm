# Project Roles

## Purpose
Clarify the difference between organization RBAC permissions and project-level membership roles.

## Organization RBAC (Module Access)
Organization roles grant module permissions such as:
- `projects.view` — view projects, widgets, and read-only data
- `projects.create` — create projects
- `projects.edit` — edit project records
- `projects.manage` — full project administration including membership and catalogs

These permissions are seeded by `ProjectPermissionSeeder` and synced to organization roles from `config/rbac.php`. The dynamic RBAC `project-manager` system role includes project and task permissions for delivery teams.

## Project-Level Roles (Membership)
Project roles apply to individual team members on a project. They are **config-driven** (no database catalog table) and stored on `project_members.project_role`.

| Config Key | Label | Typical Use |
| --- | --- | --- |
| `owner` | Project Owner | Accountable executive sponsor |
| `manager` | Project Manager | Day-to-day delivery lead |
| `delivery_lead` | Delivery Lead | Workstream coordination |
| `team_lead` | Team Lead | Sub-team leadership |
| `team_member` | Team Member | Individual contributor |
| `stakeholder` | Stakeholder | Read-oriented participant |
| `viewer` | Viewer | Read-only project access |

Labels resolve from `config('projects.roles')`. `ProjectRoleSeeder` verifies the config map at seed time.

## Automatic Membership
When a project is created or leadership changes, `ProjectService` ensures the owner and manager are active members with roles `owner` and `manager` respectively.

## Relationship Summary
| Layer | Stored In | Controls |
| --- | --- | --- |
| Organization RBAC | `permissions`, `roles`, `role_permissions` | Can user access the Projects module? |
| Project role | `project_members.project_role` | What is the user's responsibility on this project? |

A user may hold `projects.view` at the organization level while acting as `manager` on one project and `viewer` on another.

## Related Documentation
See [architecture](architecture.md) and [developer-guide](developer-guide.md).
