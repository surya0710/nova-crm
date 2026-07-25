# HRMS User Guide - Employees

## Purpose
Manage employee profiles, employment details, and optional login accounts.

## Who should use this feature
HR staff, managers, and authorized operations users.

## Prerequisites
- Employee management permissions (`hrms.view` / `hrms.create` / `hrms.manage` as needed)
- Department and designation structures configured

## Step-by-step instructions
1. Create or open an employee profile.
2. Update required personal and job details.
3. Assign reporting manager and employment settings.
4. Optionally enable **Create Login Account** so the employee can activate access via invitation (no admin-assigned passwords).
5. Save and verify profile completeness.

## Login & Employee Workspace
See [Employee Login & Workspace](employee-login.md) for invitations, bulk provisioning, portal enable/disable, and troubleshooting.

The Employee Workspace is the existing ESS experience (`/ess`). There is no separate `/portal` application.

## Expected result
Employee data is accurate, searchable, and policy-compliant. When login is provisioned, the employee can activate their own account and use authorized modules.

## Best Practices
Use validated data standards and periodic profile reviews. Prefer invitations for all new access; use bulk generation for large cohorts.

## Common Mistakes
Incomplete records, outdated manager assignments, and sharing temporary passwords (unsupported — always use invitations).

## FAQ
Document profile updates, transfers, and access concerns. For locked or expired invitations, use Resend Invitation / Unlock on the employee profile.
