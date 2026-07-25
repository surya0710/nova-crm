# HR Configuration Guide

## Overview

HR configuration lives under **Organization Settings**, not the HR sidebar.

HRMS operational pages (employees, leave applications, attendance records, payroll runs) continue under HR.

## Working Days

Path: Organization Settings → Working Days

Stored in `organizations.settings.working_days` / `weekend_days`.

`LeaveService` prefers organization working days and falls back to `config/hrms.php`.

## Leave Types & Holiday Calendar

Still use the shared HRMS controllers/services. Navigation aliases:

- `/organization/settings/leave-types` → leave types
- `/organization/settings/holidays` → holiday calendar

Leave type rows continue to encode paid/approval/carry-forward policy fields.

## Leave Policies

Organization defaults:

- Require manager approval
- Require HR approval
- Allow negative balance
- Cancellation cutoff days

Individual leave types may override.

## Leave Approvers

Defines primary chain (`reporting_manager`, `department_head`, `hr`) and HR fallback. Approval queue execution remains in Leave Applications.

## Attendance Rules

Organization defaults for grace, late threshold, early clock-in window, and overtime approval. Shift-level grace/overtime still apply per shift.

## Shift Management

Configured in Organization Settings. Assignments remain operational under HR → Shift Assignments.
