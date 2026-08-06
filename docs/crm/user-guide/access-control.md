# Access Control User Guide

## Overview

Organization administrators can manage roles, permissions, and user access from **Settings → Access Control**.

## Roles

- View all organization roles sorted by hierarchy
- Create custom roles with name, color, and hierarchy level
- Edit, duplicate, activate, or deactivate roles
- System roles are protected and cannot be deleted

## Permissions

- Browse the permission catalog
- Filter by group, module, or search term
- Activate or deactivate organization-specific permissions

## Permission Groups

- View permission groups (CRM, HRMS, Settings, etc.)
- Create custom groups for organization-specific permissions
- Archive groups that are no longer needed

## Permission Matrix

- View a grid of roles vs permissions
- Filter by module
- Bulk assign or remove permissions
- Save all changes at once

## User Roles

- Assign multiple roles to a team member
- Set a primary role for the main permission set
- View effective permissions (combined from all assigned roles)

## Permission Templates

- Preview template roles and permissions before installing
- Install templates for Corporate, Startup, Agency, Healthcare, or Education
- Reset organization roles to the default template

## Notifications

Users receive in-app notifications when:

- A role is assigned or removed
- A permission template is installed

## Required Permissions

| Action | Permission |
|--------|------------|
| View access control | `rbac.view` |
| Manage roles | `rbac.roles.manage` |
| Manage permissions | `rbac.permissions.manage` |
| Install templates | `rbac.templates.manage` |

Organization owners and administrators have full access by default.
