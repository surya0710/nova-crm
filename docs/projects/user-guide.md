# Projects User Guide

## Purpose
Guide project owners, managers, and team members through everyday project operations.

## Who should use this feature
- Project owners and managers
- Delivery team members
- Stakeholders with project visibility

## Prerequisites
- Organization membership with `projects.view` (or higher)
- Project catalogs seeded (`ProjectFoundationSeeder`)
- Optional: CRM customer record when linking a client

## Step-by-step instructions
1. Open the workspace dashboard and review **My Projects**, **Active Projects**, **Project Deadlines**, and **Project Milestones** widgets.
2. Create a project with name, owner, manager, category, type, and planned dates.
3. Assign team members with appropriate project roles (owner, manager, team member, viewer).
4. Add milestones with due dates to track delivery checkpoints.
5. Link tasks to the project for execution tracking (Tasks module).
6. Update completion percentage and lifecycle stage as work progresses.
7. Change operational status (e.g., Draft → Active → Completed) when appropriate.
8. Archive completed or cancelled projects to remove them from active views.

## Dashboard widgets
| Widget | Shows |
| --- | --- |
| My Projects | Projects where you are owner, manager, or active member |
| Active Projects | Organization projects in Active status |
| Project Deadlines | Projects with planned end dates in the next 30 days |
| Project Milestones | Upcoming pending or in-progress milestones |

## Expected result
Projects appear on dashboards and detail views with accurate status, lifecycle stage, membership, and milestone progress. Archived projects become read-only.

## Permissions summary
- `projects.view` — browse projects and widgets
- `projects.create` — create new projects
- `projects.edit` — update project details, milestones, and lifecycle
- `projects.manage` — membership, archive, and catalog administration

## Related Documentation
See [overview](overview.md), [lifecycle](lifecycle.md), [roles](roles.md), and [administrator-guide](administrator-guide.md).
