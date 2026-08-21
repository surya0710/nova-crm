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

## Commercial CRM permissions

| Action | Permission |
|--------|------------|
| View / manage products and categories | `products.view` / `create` / `update` / `delete` / `manage` |
| View / manage quotations | `quotations.view` / `create` / `update` / `delete` / `manage` |
| Send quotation email | `quotations.update` (`changeStatus`) |
| Convert quotation → sales order | `sales_orders.create` **and** `quotations.view` |
| View / manage sales orders | `sales_orders.view` / `create` / `update` / `delete` / `manage` |
| Convert sales order → invoice | `invoices.create` **and** `sales_orders.view` |
| View / manage invoices | `invoices.view` / `create` / `update` / `delete` / `manage` |
| Issue, cancel, or email invoices | `invoices.update` |
| View / record payments | `payments.view` / `payments.create` |
| Receivables and customer ledger | `invoices.view` **or** `finance.view` |
| Credit / debit notes | `adjustment_notes.view` / `create` / `update` / `delete` / `manage` |
| Price lists | `price_lists.view` / `create` / `update` / `delete` / `manage` |
| Contacts, ticket workspace, and company tickets | `customers.view` / `customers.update` (no extra ticket slugs) |
| Sales activities | `customers.view` to list; `customers.update` to log/complete (opportunity update also allowed on deals) |
| REST commercial APIs | `api.access` plus the entity permission above |
| Customer portal billing | `auth:client` linked to the customer (not staff RBAC) |

Product categories reuse the products permission set (no extra slugs). Documents are organization-scoped; other tenants receive 404.

Default roles: managers have full commercial access; sales executives sell (quotes/orders, not invoices/payments); support can view invoices, payments, receivables, and notes; HR has no commercial catalog or billing access.

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

## CRM email

| Action | Permission |
|--------|------------|
| View conversations, delivery, metrics | `crm_email.view` or `customers.view` |
| Send from records / API | `customers.create` or `customers.update` (plus the record’s own permission) |
| View templates | `email_templates.view` |
| Manage templates | `email_templates.manage` |
| REST API | `api.access` in addition to the row above |

Mail configuration, templates, messages, conversations, attachments, and webhook tokens are organization-scoped. Other-organization URLs return 404. SMTP passwords and webhook secrets are never rendered in APIs.
