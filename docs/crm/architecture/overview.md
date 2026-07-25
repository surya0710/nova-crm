# CRM Architecture Overview

## Purpose
Technical structure of CRM internals and integrations.

## Diagram
CRM UI -> CRM Controllers -> CRM Services -> CRM Database

## Database Tables
Leads, contacts, customers, opportunities, quotations, invoices, payments.

## Services
Lifecycle management, pricing logic, billing orchestration, reporting.

## Controllers
HTTP controllers for lead, customer, opportunity, and billing domains.

## Policies
Role-based authorization for sales and finance operations.

## Workflow Events
Lead created, opportunity updated, invoice issued, payment recorded.

## Notifications
Assignment reminders, approval alerts, and overdue notices.

## Audit
Track edits on pipeline, quotations, invoices, and payments.

## RBAC
Sales rep, sales manager, finance, and admin permission scopes.

## Extension Points
Custom fields, automation triggers, and provider integrations.

## Future Improvements
Advanced forecasting and automated risk scoring.
