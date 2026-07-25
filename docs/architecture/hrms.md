# HRMS Architecture

## Purpose
Technical blueprint for HRMS module components.

## Diagram
HRMS UI -> HRMS Controllers -> HRMS Services -> HRMS Tables

## Database Tables
Employees, attendance, leave, payroll, goals, reviews, feedback.

## Services
Workforce profile, attendance rules, leave engine, payroll processing.

## Controllers
Document HRMS HTTP controllers and domain boundaries.

## Policies
Permissions for HR admin, manager, employee self-service.

## Workflow Events
Employee updated, leave approved, payroll published, review submitted.

## Notifications
Leave status, payroll availability, review deadlines.

## Audit
Capture sensitive profile and compensation changes.

## RBAC
Separate access for HR operations vs employee self-service.

## Extension Points
Policy engines, payroll adapters, and review workflows.

## Future Improvements
Scalability and regional compliance enhancements.
