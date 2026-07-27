# File Upload APIs

File upload capabilities in NovaCRM APIs.

---

## HRMS Employee Documents

**Not available.** No API for uploading ESS employee documents.

Web upload: HR admin via `/hrms/employees/{employee}/documents` (`hrms.manage`).

---

## Task Attachments (Projects Module)

### POST `/api/v1/tasks/{task}/attachments`

Upload file to a task.

| Requirement | Value |
|-------------|-------|
| Permission | `tasks.update` via `TaskPolicy::attachments` |
| Module | `projects` |
| API access | `api.access` |
| Content-Type | `multipart/form-data` |

#### Request

| Field | Rules |
|-------|-------|
| `file` | required, file, max size, mimes |

From `StoreTaskAttachmentRequest` and `config/attachments.php`:

| Constraint | Value |
|------------|-------|
| Max size | `10240` KB (10 MB) — `attachments.max_size_kb` |
| Max files per entity | 10 — `attachments.max_files` |
| Allowed MIME types | pdf, jpg, jpeg, png, gif, webp, doc, docx, xls, xlsx, csv, txt, zip |

#### Example

```http
POST /api/v1/tasks/42/attachments HTTP/1.1
Authorization: Bearer {token}
X-Organization-Id: 42
Content-Type: multipart/form-data

--boundary
Content-Disposition: form-data; name="file"; filename="report.pdf"
Content-Type: application/pdf

(binary data)
--boundary--
```

#### Success `201`

`TaskAttachmentResource`:

```json
{
  "data": {
    "id": 1,
    "task_id": 42,
    "filename": "report.pdf",
    "file_size": 102400,
    "mime_type": "application/pdf",
    "uploaded_by": 5,
    "created_at": "2026-07-21T12:00:00+00:00"
  }
}
```

#### Error `422`

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "file": ["The file must be a file of type: pdf, jpg, ..."]
  }
}
```

---

### GET `/api/v1/tasks/{task}/attachments`

List attachments (paginated, default 50 per page).

---

### GET `/api/v1/tasks/{task}/attachments/{attachment}/download`

Download attachment file stream.

**Permission:** `tasks.view`

---

### DELETE `/api/v1/tasks/{task}/attachments/{attachment}`

Delete attachment.

---

## Import Upload (Admin)

`POST /api/v1/imports/sessions` — bulk data import, not for mobile ESS.

---

## Export Download (Admin)

`GET /api/v1/exports/sessions/{session}/download` — async export download.

---

## Multipart Request Guidelines

1. Use `multipart/form-data` encoding
2. Field name must be `file` for task attachments
3. Include standard auth headers
4. Do not set `Content-Type: application/json` for uploads
5. Handle 413 if reverse proxy limits differ from app limit

---

## Validation Summary

| Rule | Task attachments |
|------|------------------|
| Required | `file` |
| Max size | 10240 KB |
| Types | pdf, jpg, jpeg, png, gif, webp, doc, docx, xls, xlsx, csv, txt, zip |

---

## Future: ESS Document Upload

When implemented, expect:

```
POST /api/v1/ess/documents
Content-Type: multipart/form-data

file: (binary)
category: offer_letter
name: Optional
```

With HRMS-specific MIME restrictions (likely similar to `attachments` config).

---

## Security

- Files stored on configured disk (local/S3)
- Authorization enforced per task/document policy
- Virus scanning not implemented in codebase
- Use HTTPS for all uploads
