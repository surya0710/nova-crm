# CRM Architecture

## Purpose
Technical blueprint for CRM module internals.

## Diagram
CRM UI -> CRM Controllers -> CRM Services -> CRM Tables

## Database Tables
Leads, customers, contacts, opportunities, quotations, invoices, payments.

## Services
Lead lifecycle, pipeline management, billing integration, reporting.

## Controllers
Document CRM HTTP controllers and responsibility split.

## Policies
Role-based access for sales, finance, and admin functions.

## Workflow Events
Lead created, opportunity updated, invoice issued, payment posted.

## Notifications
Assignment alerts, follow-up reminders, and status updates.

## Audit
Track lifecycle edits and financial action history.

## RBAC
Map permissions for sales rep, manager, and finance roles.

## Extension Points
Custom fields, automations, and external connector hooks.

## Future Improvements
Pipeline analytics and advanced segmentation support.
