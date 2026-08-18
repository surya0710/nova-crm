# CRM Architecture Overview

## Purpose
Technical structure of CRM internals and integrations.

## Diagram
CRM UI -> CRM Controllers -> CRM Services -> CRM Database

## Database Tables
Leads, contacts, customers, product catalog, opportunities, quotations, invoices, payments.

## Services
Lifecycle management, GST tax engine, quotation/invoice PDFs, billing orchestration, commercial timeline, reporting widgets.

## Controllers
HTTP controllers for lead, customer, opportunity, catalog, and billing domains (web + `/api/v1`).

## Policies
Role-based authorization for sales and finance operations; tenant isolation via organization scope.

## Workflow Events
Lead created, opportunity updated, quotation sent/converted, invoice issued, payment recorded.

## Notifications
Assignment reminders, approval alerts, and overdue notices.

## Audit
Track edits on pipeline, quotations, invoices, and payments. Surface commercial events on the customer timeline.

## RBAC
Sales rep, sales manager, finance, and admin permission scopes (`products.*`, `quotations.*`, `invoices.*`).

## Extension Points
Custom fields, automation triggers, dashboard widget providers, and REST APIs.

## Future Improvements
Advanced forecasting and automated risk scoring.
