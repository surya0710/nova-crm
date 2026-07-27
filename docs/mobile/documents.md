# Document APIs

Employee document access for mobile.

---

## API Availability

| Endpoint | Status |
|----------|--------|
| `GET /api/v1/ess/documents` | **Not implemented** |
| `GET /api/v1/ess/documents/{id}` | **Not implemented** |
| `GET /api/v1/ess/documents/{id}/download` | **Not implemented** |
| `GET /api/v1/ess/documents/categories` | **Not implemented** |

---

## Web ESS Implementation

**Controller:** `EssDocumentController`  
**Prefix:** `/hrms/ess/documents`  
**Middleware:** `permission:ess.access`

| Method | Route | Action |
|--------|-------|--------|
| GET | `/hrms/ess/documents` | List own documents |
| GET | `/hrms/ess/documents/{document}` | View document metadata |
| GET | `/hrms/ess/documents/{document}/download` | Download file |

Authorization via `EmployeeDocumentPolicy` — employees can only access their own documents.

---

## Document Categories

From `config/hrms.php` → `document_categories`:

| Key | Label |
|-----|-------|
| `aadhaar` | Aadhaar |
| `pan` | PAN |
| `passport` | Passport |
| `driving_license` | Driving License |
| `offer_letter` | Offer Letter |
| `appointment_letter` | Appointment Letter |
| `experience_letter` | Experience Letter |
| `certificate` | Educational Certificate |
| `salary_document` | Salary Document |
| `other` | Other HR Document |

---

## HR Admin Document Management (Web)

**Route:** `/hrms/employees/{employee}/documents`  
**Controller:** `HrmsEmployeeDocumentController`  
**Permission:** `hrms.manage`

Upload, verify, and manage employee documents (HR admin).

---

## File Upload

No API for employee document upload from ESS.

Task attachment upload exists for projects module only — see [uploads.md](./uploads.md).

---

## Recommended Future API

```
GET  /api/v1/ess/documents
GET  /api/v1/ess/documents/{id}
GET  /api/v1/ess/documents/{id}/download
GET  /api/v1/ess/documents/categories
```

### Example List Response

```json
{
  "data": [
    {
      "id": 1,
      "name": "Offer Letter.pdf",
      "category": "offer_letter",
      "category_label": "Offer Letter",
      "file_size": 245760,
      "mime_type": "application/pdf",
      "uploaded_at": "2026-01-15T10:00:00+00:00",
      "expires_at": null,
      "is_verified": true
    }
  ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 5
  }
}
```

Download endpoint should return file stream with appropriate `Content-Type` and `Content-Disposition`.

Permission: `ess.access` + own documents only.

---

## Future: Document Upload (Not Implemented)

```
POST /api/v1/ess/documents
Content-Type: multipart/form-data

file: (binary)
category: offer_letter
name: Optional display name
```

Would require new validation rules and storage policy.

---

## Mobile Screen Mapping

```
Documents Screen
  ↓
[NOT AVAILABLE — web only at /hrms/ess/documents]
```

---

## Security Notes

- Documents may contain PII — enforce HTTPS
- Download URLs should be authenticated (Bearer token or signed temporary URL)
- Audit document access in future API implementation
