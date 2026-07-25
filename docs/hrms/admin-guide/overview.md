# HRMS Administrator Guide

## Purpose
Define configuration and governance controls for HRMS operations.

## Required configuration
- Organization structure, departments, and designations
- Attendance and shift policies
- Leave and payroll policy rules
- Performance cycle settings

## Permissions
- `hrms.view`
- `attendance.*`
- `leave.*`
- `payroll.*`
- `performance.*`

## Dependencies
- Employee records and reporting hierarchy
- Statutory and payroll profiles
- Notification and approval workflows

## Configuration Steps
1. Configure org structure and employee master data.
2. Configure attendance, leave, and payroll policy rules.
3. Configure review cycles, templates, and approvals.
4. Verify HR, manager, and ESS role behavior.

## Best Practices
Use role-based least privilege and schedule monthly compliance checks.

## Troubleshooting
For failures, validate policy setup, effective dates, permissions, and workflow status.
