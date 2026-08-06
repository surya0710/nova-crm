# Project Templates

## Overview
Reusable blueprints for spinning up projects with milestones, tasks, checklists, and labels. Templates may be organization-owned or system-wide (`organization_id` null + `is_system`). Cloning creates a real project via `TemplateCloneService`.

## Database Tables
| Table | Model | Purpose |
| --- | --- | --- |
| `project_templates` | `ProjectTemplate` | Template header (name, slug, category, industry, defaults, metadata) |
| `template_milestones` | `TemplateMilestone` | Planned milestones with offset/duration days |
| `template_tasks` | `TemplateTask` | Nested task blueprint (priority, assignee role, offsets) |
| `template_checklists` | `TemplateChecklist` | Checklist items on template tasks |
| `template_labels` | `TemplateLabel` | Labels to materialize on clone |

Unique: `(organization_id, slug)` on templates.

## Services
`ProjectTemplateService`:
- `create` / `update` / `delete` / `duplicate` / `toggleFavorite`
- `saveFromProject` — capture an existing project as a template
- `list` — includes org templates + system templates (`withoutGlobalScopes`)

`TemplateCloneService`:
- `createProjectFromTemplate` — creates project, milestones, tasks, checklists, labels; bumps `usage_count`; dispatches `ProjectCreatedFromTemplate`

## RBAC Permissions
| Slug | Capability |
| --- | --- |
| `projects.templates.view` | Browse and open templates |
| `projects.templates.create` | Create templates |
| `projects.templates.update` | Edit templates |
| `projects.templates.delete` | Delete templates |
| `projects.templates.manage` | Full template administration |

## Workflow Events
| Trigger | Event |
| --- | --- |
| `project.template.created` | `ProjectTemplateCreated` |
| `project.template.updated` | `ProjectTemplateUpdated` |
| `project.template.deleted` | `ProjectTemplateDeleted` |
| `project.created_from_template` | `ProjectCreatedFromTemplate` |

## API / Routes Summary
| Surface | Routes |
| --- | --- |
| Web | Resource `project-templates`; `POST .../create-project`, `duplicate`, `favorite` |
| API | `api/project-templates` resource + create-project / duplicate / favorite |

## UI
Blade under `resources/views/projects/templates/` (`index`, `create`, `edit`, `show`, `_form`). Dashboard quick action for create; widget gated by `projects.templates.view`. Global search matches name, description, and slug.

## Acceptance Notes
- System templates are not editable unless explicitly allowed.
- Slug generation is unique per organization (including null org for system).
- Clone applies `offset_days` from the chosen start date.
- Search includes system templates for the current tenant context.
