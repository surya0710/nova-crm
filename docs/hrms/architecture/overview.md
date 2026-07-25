# HRMS Architecture Overview

## Purpose
Technical map of HRMS domains and integrations.

## Diagram
HRMS UI -> HRMS Controllers -> HRMS Services -> HRMS Database

## Database Tables
Employees, attendance, leave, payroll, goals, reviews, feedback, appraisal.

## Services
Profile management, policy engines, payroll calculators, review workflows.

## Controllers
Domain controllers for HR operations and employee self-service.

## Policies
Authorization boundaries for HR admin, manager, and employee roles.

## Workflow Events
Employee updated, leave approved, payroll published, review submitted.

## Notifications
Attendance reminders, leave status, payroll and review alerts.

## Audit
Track sensitive data changes and approval histories.

## RBAC
Permission matrix by HR, manager, payroll, and employee scopes.

## Extension Points
Policy plugins, payroll adapters, and review workflow hooks.

## Future Improvements
Regional compliance packs and advanced analytics support.
