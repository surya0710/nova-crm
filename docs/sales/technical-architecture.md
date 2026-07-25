# Technical Architecture (Sales Leave-behind)

## Stack
Laravel · Blade + Alpine · Vite · Tailwind · MySQL · Sanctum API · Queue workers · Scheduler

## Logical architecture
```
Browser → Web (tenant app) → Application services → DB
                ↓
         Queue workers / Scheduler
                ↓
         Platform console (/platform) — operators only
```

## Workspaces
CRM · Projects · HRMS · Marketing · Analytics · Administration · Platform

## Integration surfaces
- REST API (Sanctum tokens)
- Marketing provider connectors / webhooks
- Workflow engine hooks

## Operations
Health `GET /up` · Platform monitoring · Deploy via documented runbooks

Deep dive: [architecture/overall-system.md](../architecture/overall-system.md)