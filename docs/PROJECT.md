# PPAS API — Project Documentation

> **Purpose of this document:** Complete navigation guide for agents and frontend developers. Covers file structure, API contract, response shapes, business rules, and integration patterns.

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Tech Stack](#2-tech-stack)
3. [Directory Structure](#3-directory-structure)
4. [API Integration Guide (Frontend)](#4-api-integration-guide-frontend)
   - [Base URL & Versioning](#41-base-url--versioning)
   - [Authentication](#42-authentication)
   - [Response Envelope](#43-response-envelope)
   - [Pagination](#44-pagination)
   - [Error Handling](#45-error-handling)
5. [Domain Reference](#5-domain-reference)
   - [Auth](#51-auth-domain)
   - [Config](#52-config-domain)
   - [Procurement](#53-procurement-domain)
   - [RFQ](#54-rfq-domain)
   - [Resolution](#55-resolution-domain)
   - [Monitoring](#56-monitoring-domain)
   - [Dashboard](#57-dashboard)
6. [Purchase Request Lifecycle](#6-purchase-request-lifecycle)
7. [Roles & Permissions](#7-roles--permissions)
8. [File Download Patterns](#8-file-download-patterns)
9. [Resource Shapes](#9-resource-shapes)
10. [App Layer Map](#10-app-layer-map)

---

## 1. Project Overview

**PPAS** (Procurement Planning and Administration System) is a Laravel REST API that digitises the end-to-end procurement workflow for a government agency (NIA). The system handles:

- Purchase request creation and approval routing
- Request for Quotation (RFQ) and canvassing
- Abstract of Quotation, BAC Resolution, and Notice of Award
- Purchase Order generation
- Supplier management
- PDF document generation (request forms, purchase requests, purchase orders)
- Audit logging, login tracking, and in-system notifications

The API is **API-first** — no server-rendered HTML for data. All responses follow a shared JSON envelope (see §4.3).

---

## 2. Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.3+ |
| Framework | Laravel 13.x |
| Auth | Laravel Sanctum (Bearer token) |
| Database (local/test) | SQLite (in-memory for tests) |
| Database (production) | MySQL (compatible — dual SQL paths in DashboardService) |
| Testing | PHPUnit 12 |
| Code style | Laravel Pint |
| PDF generation | Blade templates → streamed HTTP response |
| File storage | Laravel private disk (files never on public URL) |

---

## 3. Directory Structure

```
ppas-api/
├── app/
│   ├── Exceptions/
│   │   └── InvalidStatusTransitionException.php   # Thrown on illegal PR status jump → 422
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── V1/                                # All controllers live under V1 namespace
│   │   │       ├── Auth/
│   │   │       │   ├── AuthController.php          # login / logout / me
│   │   │       │   └── UserController.php          # CRUD for user accounts
│   │   │       ├── Config/
│   │   │       │   ├── CategoryController.php      # Procurement categories
│   │   │       │   ├── OfficeController.php        # Office/department management
│   │   │       │   └── RoleController.php          # System roles
│   │   │       ├── DashboardController.php         # KPI + analytics summary
│   │   │       ├── Monitoring/
│   │   │       │   ├── AuditLogController.php      # Read-only audit trail
│   │   │       │   ├── LoginLogController.php      # Read-only login history
│   │   │       │   └── NotificationController.php  # In-system notifications
│   │   │       ├── Procurement/
│   │   │       │   ├── PrAttachmentController.php       # File attachments on PRs
│   │   │       │   ├── PrStatusHistoryController.php    # Immutable PR status log
│   │   │       │   ├── PurchaseOrderController.php      # Purchase orders + PDF download
│   │   │       │   ├── PurchaseOrderItemController.php  # Line items on POs
│   │   │       │   ├── PurchaseRequestController.php    # PRs + PDF downloads
│   │   │       │   ├── PurchaseRequestItemController.php# Line items on PRs
│   │   │       │   ├── SupplierController.php           # Supplier registry + logo download
│   │   │       │   └── SupplierDocumentController.php   # Supplier documents + download
│   │   │       ├── Resolution/
│   │   │       │   ├── BacResolutionController.php      # BAC Resolution documents
│   │   │       │   └── NoticeOfAwardController.php      # Notice of Award documents
│   │   │       └── RFQ/
│   │   │           ├── AbstractOfQuotationController.php # Abstract of Quotation
│   │   │           ├── CanvassResponseController.php     # Supplier quotation responses
│   │   │           ├── RfqController.php                 # Request for Quotation
│   │   │           └── RfqItemController.php             # Line items on RFQs
│   │   ├── Requests/                              # FormRequest — validation + authorize()
│   │   │   ├── Auth/
│   │   │   ├── Config/
│   │   │   ├── Procurement/
│   │   │   ├── Resolution/
│   │   │   └── RFQ/
│   │   └── Resources/                             # API Resources — shape of JSON output
│   │       └── *.php                              # One resource per model
│   ├── Models/                                    # Eloquent models (PHP 8 #[Fillable] attrs)
│   ├── Policies/                                  # Gate/Policy — per-model authorization
│   ├── Providers/
│   │   └── AppServiceProvider.php                 # Policy registration
│   ├── Services/                                  # Business logic layer
│   │   ├── AuditLogger.php                        # Static helper — writes audit_logs rows
│   │   ├── AuthService.php                        # Login/token creation
│   │   ├── DashboardService.php                   # KPI + chart queries
│   │   ├── NotificationService.php                # In-system notification dispatch
│   │   ├── PdfService.php                         # PDF streaming (Blade → response)
│   │   ├── PrStatusHistoryService.php             # Appends immutable status rows
│   │   ├── PurchaseRequestService.php             # PR CRUD + transition engine + audit
│   │   └── *.php                                  # One service per domain resource
│   └── Support/
│       └── PurchaseRequestTransitions.php         # Role-permission map for PR transitions
├── bootstrap/
│   └── app.php                                    # Route registration + global exception → JSON envelope
├── config/                                        # Standard Laravel config files
├── database/
│   ├── factories/
│   ├── migrations/                                # All schema migrations (prefixed 2026_06_*)
│   └── seeders/                                   # Role, Office, Category, User seeders
├── resources/
│   └── views/pdf/                                 # Blade templates for PDF generation
│       ├── purchase-order.blade.php
│       ├── purchase-request.blade.php
│       └── request-form.blade.php
├── routes/
│   └── api.php                                    # All API routes under /api/v1/*
├── storage/
│   └── app/private/                               # Private disk — PR attachments, supplier docs
└── tests/
    └── Feature/                                   # Feature tests mirror controller namespaces
        ├── Auth/
        ├── Config/
        ├── Monitoring/
        ├── Procurement/
        ├── Resolution/
        └── RFQ/
```

---

## 4. API Integration Guide (Frontend)

### 4.1 Base URL & Versioning

```
Base URL:  http://<host>/api/v1
```

All routes are prefixed with `/api/v1/`. There is no second version yet.

---

### 4.2 Authentication

The API uses **Laravel Sanctum Bearer tokens**.

**Login flow:**
1. `POST /api/v1/auth/login` → receive `data.token`
2. Store the token (memory / secure storage — not localStorage in prod)
3. Send on every subsequent request:

```http
Authorization: Bearer <token>
```

**Logout:** `POST /api/v1/auth/logout` — revokes the current token server-side.

**Rate limiting on login:** 10 requests per minute per IP. Exceeding this returns HTTP 429.

**Fetch authenticated user:** `GET /api/v1/auth/me` — returns the full `UserResource` with `role` and `office` loaded.

---

### 4.3 Response Envelope

Every API response — success or failure — uses this exact shape:

```jsonc
{
  "data": <resource | resource[] | null>,
  "message": "Human-readable summary.",
  "errors": <object | null>    // only populated on 422 validation failures
}
```

**Success (single resource):**
```json
{
  "data": { "id": 1, "..." },
  "message": "Purchase request retrieved successfully.",
  "errors": null
}
```

**Validation failure (422):**
```json
{
  "data": null,
  "message": "Validation failed.",
  "errors": {
    "email": ["The email field is required."],
    "purpose": ["The purpose field is required."]
  }
}
```

**Not found (404):**
```json
{
  "data": null,
  "message": "Resource not found.",
  "errors": null
}
```

**Unauthenticated (401):**
```json
{
  "data": null,
  "message": "Unauthenticated.",
  "errors": null
}
```

**Invalid status transition (422):**
```json
{
  "data": null,
  "message": "Cannot transition from 'draft' to 'completed'.",
  "errors": null
}
```

---

### 4.4 Pagination

List endpoints that paginate return:

```jsonc
{
  "data": [ /* array of resources */ ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 73
  },
  "message": "...",
  "errors": null
}
```

Pass `?page=N` to navigate pages. Default page size is **15**.

---

### 4.5 Error Handling

| HTTP Status | Meaning | `errors` field |
|---|---|---|
| 200 | OK | null |
| 201 | Created | null |
| 401 | Unauthenticated | null |
| 403 | Forbidden (policy denied) | null |
| 404 | Not found | null |
| 422 | Validation failed OR invalid status transition | object (validation) or null (transition) |
| 429 | Too many requests (login rate limit) | null |
| 500 | Server error (production only) | null |

---

## 5. Domain Reference

### 5.1 Auth Domain

| Method | Endpoint | Auth required | Description |
|---|---|---|---|
| POST | `/auth/login` | No | Login; returns `{ token, user }` |
| POST | `/auth/logout` | Yes | Revoke current token |
| GET | `/auth/me` | Yes | Get authenticated user with role + office |

**Login request body:**
```json
{ "email": "user@example.com", "password": "secret" }
```

**Login success response `data`:**
```json
{
  "token": "1|abcdef...",
  "user": { /* UserResource */ }
}
```

---

**User management** (admin-level, roles policy-gated):

| Method | Endpoint | Description |
|---|---|---|
| GET | `/users` | List all users (paginated) |
| POST | `/users` | Create a user |
| GET | `/users/{id}` | Get a user |
| PUT/PATCH | `/users/{id}` | Update a user |
| DELETE | `/users/{id}` | Delete a user |

---

### 5.2 Config Domain

Config resources are lookup tables used in dropdowns and foreign keys.

| Method | Endpoint | Description |
|---|---|---|
| GET/POST | `/roles` | List / create roles |
| GET/PUT/DELETE | `/roles/{id}` | Show / update / delete role |
| GET/POST | `/offices` | List / create offices |
| GET/PUT/DELETE | `/offices/{id}` | Show / update / delete office |
| GET/POST | `/categories` | List / create categories |
| GET/PUT/DELETE | `/categories/{id}` | Show / update / delete category |

These are small, non-paginated or paginated lookup sets used to populate selects for `role_id`, `office_id`, `category_id`.

---

### 5.3 Procurement Domain

#### Purchase Requests

| Method | Endpoint | Description |
|---|---|---|
| GET | `/purchase-requests` | List PRs (role-filtered — requesters see own; others see non-draft) |
| POST | `/purchase-requests` | Create a PR (starts as `draft`) |
| GET | `/purchase-requests/{id}` | Get PR with items, attachments, requester, office, category |
| PUT/PATCH | `/purchase-requests/{id}` | Update PR fields or advance status |
| DELETE | `/purchase-requests/{id}` | Delete PR |
| GET | `/purchase-requests/{id}/download/request-form` | Download RF PDF (requires `rf_number`) |
| GET | `/purchase-requests/{id}/download/purchase-request` | Download PR PDF (requires `pr_number`) |

**Key business rules:**
- `rf_number` is auto-generated when status transitions `draft → submitted` (format: `RF-YYYY-NNNNN`)
- `pr_number` is auto-generated when status transitions `forwarded_to_ppu → pr_prepared` (format: `PR-YYYY-NNNNN`)
- `requires_philgeps` is auto-computed: `true` when `total_amount >= 50000`
- `alobs_number` is required when transitioning to `budget_approved`
- `remarks` is required when transitioning to `returned` or `disapproved`

**Store request body:**
```json
{
  "requester_id": 3,
  "requesting_office_id": 2,
  "category_id": 1,
  "purpose": "Office supplies for Q3",
  "total_amount": 12500.00
}
```

**Status transition body (PATCH):**
```json
{ "status": "submitted" }
```

#### Purchase Request Items

| Method | Endpoint | Description |
|---|---|---|
| GET | `/purchase-request-items` | List all PR line items |
| POST | `/purchase-request-items` | Add a line item to a PR |
| GET | `/purchase-request-items/{id}` | Get item |
| PUT/PATCH | `/purchase-request-items/{id}` | Update item |
| DELETE | `/purchase-request-items/{id}` | Delete item |

#### PR Attachments

| Method | Endpoint | Description |
|---|---|---|
| GET | `/pr-attachments` | List attachments |
| POST | `/pr-attachments` | Upload a file attachment (multipart/form-data) |
| GET | `/pr-attachments/{id}` | Get attachment metadata |
| PUT/PATCH | `/pr-attachments/{id}` | Update metadata |
| DELETE | `/pr-attachments/{id}` | Delete attachment |
| GET | `/pr-attachments/{id}/download` | Download file (streamed, private disk) |

Files are stored on the **private disk** — they are never publicly accessible. Always use the `/download` route with a valid Bearer token.

#### PR Status Histories

Read-only. Append-only log of every status transition on a PR.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/pr-status-histories` | List all status history rows |
| GET | `/pr-status-histories/{id}` | Get one row |

#### Suppliers

| Method | Endpoint | Description |
|---|---|---|
| GET | `/suppliers` | List suppliers |
| POST | `/suppliers` | Create supplier |
| GET | `/suppliers/{id}` | Get supplier |
| PUT/PATCH | `/suppliers/{id}` | Update supplier |
| DELETE | `/suppliers/{id}` | Delete supplier |
| GET | `/suppliers/{id}/logo` | Download supplier logo (streamed, private disk) |

The `logo_url` field in `SupplierResource` returns the authenticated logo URL automatically when a logo exists.

#### Supplier Documents

No `update` endpoint — delete and re-upload instead.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/supplier-documents` | List |
| POST | `/supplier-documents` | Upload document |
| GET | `/supplier-documents/{id}` | Get metadata |
| DELETE | `/supplier-documents/{id}` | Delete |
| GET | `/supplier-documents/{id}/download` | Download file (private disk) |

#### Purchase Orders

| Method | Endpoint | Description |
|---|---|---|
| GET | `/purchase-orders` | List |
| POST | `/purchase-orders` | Create |
| GET | `/purchase-orders/{id}` | Get PO with items, supplier |
| PUT/PATCH | `/purchase-orders/{id}` | Update |
| DELETE | `/purchase-orders/{id}` | Delete |
| GET | `/purchase-orders/{id}/download/purchase-order` | Download PO PDF |

#### Purchase Order Items

| Method | Endpoint | Description |
|---|---|---|
| GET | `/purchase-order-items` | List |
| POST | `/purchase-order-items` | Add line item |
| GET | `/purchase-order-items/{id}` | Get |
| PUT/PATCH | `/purchase-order-items/{id}` | Update |
| DELETE | `/purchase-order-items/{id}` | Delete |

---

### 5.4 RFQ Domain

#### RFQs (Request for Quotation)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/rfqs` | List |
| POST | `/rfqs` | Create |
| GET | `/rfqs/{id}` | Get RFQ with items |
| PUT/PATCH | `/rfqs/{id}` | Update |
| DELETE | `/rfqs/{id}` | Delete |

#### RFQ Items

| Method | Endpoint | Description |
|---|---|---|
| GET | `/rfq-items` | List |
| POST | `/rfq-items` | Create |
| GET | `/rfq-items/{id}` | Get |
| PUT/PATCH | `/rfq-items/{id}` | Update |
| DELETE | `/rfq-items/{id}` | Delete |

#### Canvass Responses

Supplier price submissions per RFQ item.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/canvass-responses` | List |
| POST | `/canvass-responses` | Record a supplier's response |
| GET | `/canvass-responses/{id}` | Get |
| PUT/PATCH | `/canvass-responses/{id}` | Update |
| DELETE | `/canvass-responses/{id}` | Delete |

#### Abstracts of Quotation

Summary document recommending a supplier.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/abstracts-of-quotation` | List |
| POST | `/abstracts-of-quotation` | Create |
| GET | `/abstracts-of-quotation/{abstract_of_quotation}` | Get |
| PUT/PATCH | `/abstracts-of-quotation/{abstract_of_quotation}` | Update |
| DELETE | `/abstracts-of-quotation/{abstract_of_quotation}` | Delete |

> Note: The route parameter is `abstract_of_quotation` (not `id` or `abstractOfQuotation`).

---

### 5.5 Resolution Domain

#### BAC Resolutions

| Method | Endpoint | Description |
|---|---|---|
| GET | `/bac-resolutions` | List |
| POST | `/bac-resolutions` | Create |
| GET | `/bac-resolutions/{id}` | Get |
| PUT/PATCH | `/bac-resolutions/{id}` | Update |
| DELETE | `/bac-resolutions/{id}` | Delete |

#### Notices of Award

| Method | Endpoint | Description |
|---|---|---|
| GET | `/notices-of-award` | List |
| POST | `/notices-of-award` | Create |
| GET | `/notices-of-award/{notice_of_award}` | Get |
| PUT/PATCH | `/notices-of-award/{notice_of_award}` | Update |
| DELETE | `/notices-of-award/{notice_of_award}` | Delete |

> Note: The route parameter is `notice_of_award`.

---

### 5.6 Monitoring Domain

#### Notifications

In-system notifications tied to PR status changes. Cannot be created or updated via API.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/notifications` | List notifications for the authenticated user |
| GET | `/notifications/{id}` | Get one |
| PATCH | `/notifications/{id}/mark-read` | Mark as read |

#### Audit Logs

Immutable audit trail. Read-only.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/audit-logs` | List |
| GET | `/audit-logs/{id}` | Get |

#### Login Logs

Immutable login history. Read-only.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/login-logs` | List |
| GET | `/login-logs/{id}` | Get |

---

### 5.7 Dashboard

| Method | Endpoint | Description |
|---|---|---|
| GET | `/dashboard` | KPI + chart data |

**Query parameters:**

| Param | Type | Default | Description |
|---|---|---|---|
| `year` | integer | current year | Filter data by year |
| `office_id` | integer | null | Filter by requesting office |

**Response `data` shape:**
```jsonc
{
  "kpi": {
    "total_requests": 120,
    "pending_requests": 34,
    "approved_requests": 58,
    "completed_requests": 28
  },
  "budget_utilization_by_month": {
    "months": [
      { "month": 1, "label": "Jan", "total": 45000.00 },
      // ... 12 months always present
    ],
    "grand_total": 540000.00
  },
  "requests_per_section": [
    { "office": "Engineering Division", "count": 42 }
  ],
  "budget_per_section": [
    { "office": "Engineering Division", "total": 250000.00 }
  ],
  "requests_per_category": [
    { "category": "Office Supplies", "count": 30 }
  ],
  "recent_requests": [
    {
      "rf_number": "RF-2026-00001",
      "pr_number": "PR-2026-00001",
      "requester": "Juan dela Cruz",
      "office": "Admin Division",
      "total_amount": "12500.00",
      "status": "budget_approved"
    }
  ],
  "high_value_requests": [
    {
      "rf_number": "RF-2026-00020",
      "purpose": "Server hardware procurement",
      "office": "IT Division",
      "total_amount": "250000.00"
    }
  ]
}
```

Dashboard excludes `draft` status PRs from all metrics.

---

## 6. Purchase Request Lifecycle

The PR passes through a strictly ordered state machine. Status transitions are enforced server-side and will return 422 if an illegal jump is attempted.

```
draft
  └─► submitted               (Requester)
        ├─► under_review       (Procurement Officer)
        │     ├─► for_budget_approval  (Procurement Officer)
        │     │     ├─► budget_approved    (Budget Officer — must supply alobs_number)
        │     │     │     └─► forwarded_to_ppu  (Budget Officer)
        │     │     │               └─► pr_prepared     (Procurement Officer)
        │     │     │                     └─► pr_approved     (Procurement Officer)
        │     │     │                           └─► rfq_prepared    (Procurement Officer)
        │     │     │                                 └─► canvassing      (Procurement Officer)
        │     │     │                                       └─► abstract_prepared (Procurement Officer)
        │     │     │                                             └─► bac_resolution_noa (Procurement Officer)
        │     │     │                                                   └─► po_prepared  (Procurement Officer)
        │     │     │                                                         └─► completed  (Procurement Officer)
        │     │     └─► disapproved (terminal)  (Budget Officer — must supply remarks)
        │     └─► returned          (Procurement Officer — must supply remarks)
        │           └─► submitted   (Requester — re-submit)
        └─► returned               (Procurement Officer — must supply remarks)
              └─► submitted        (Requester — re-submit)
```

**Auto-generated fields:**

| Field | When generated |
|---|---|
| `rf_number` | On `draft → submitted` (format: `RF-YYYY-NNNNN`) |
| `pr_number` | On `forwarded_to_ppu → pr_prepared` (format: `PR-YYYY-NNNNN`) |
| `submitted_at` | On `draft → submitted` |
| `requires_philgeps` | Auto-computed on every save: `true` if `total_amount >= 50000` |

**Fields required for specific transitions:**

| Transition | Required field | Notes |
|---|---|---|
| Any → `budget_approved` | `alobs_number` | Allotment Obligation and Budget Slip number |
| Any → `returned` | `remarks` | Reason for return |
| Any → `disapproved` | `remarks` | Reason for disapproval |

---

## 7. Roles & Permissions

The system has four named roles. Role names are stored in the `roles` table and referenced via `User.role_id`.

| Role | Name key | Primary responsibilities |
|---|---|---|
| Requester | `requester` | Create and submit purchase requests |
| Procurement Officer | `procurement_officer` | Review PRs, manage RFQs, POs, resolutions |
| Budget Officer | `budget_officer` | Budget approval, encode ALOBS, forward to PPU |
| Admin | `admin` | User management, system config |

**Visibility rule:** Requesters see only their own PRs (including drafts). All other roles see all non-draft PRs.

**Authorization** is enforced through Laravel Policies in `app/Policies/`. Each resource has a dedicated policy. Controllers call `$this->authorize(...)` before delegating to the service. A 403 is returned when authorization fails.

**Transition-level role enforcement** lives in `app/Support/PurchaseRequestTransitions.php` — a static map of `"from:to" => [roles]`. `UpdatePurchaseRequestRequest` reads this map in its `authorize()` method.

---

## 8. File Download Patterns

Private files are **never on a public URL**. The frontend must always request files via the API with a Bearer token. The server streams the file directly.

| Resource | Download endpoint | Content |
|---|---|---|
| PR attachment | `GET /pr-attachments/{id}/download` | User-uploaded file |
| PR Request Form | `GET /purchase-requests/{id}/download/request-form` | Blade PDF — NIA RF form |
| PR Purchase Request | `GET /purchase-requests/{id}/download/purchase-request` | Blade PDF — official PR |
| Purchase Order | `GET /purchase-orders/{id}/download/purchase-order` | Blade PDF — PO document |
| Supplier logo | `GET /suppliers/{id}/logo` | Image file |
| Supplier document | `GET /supplier-documents/{id}/download` | Supplier document file |

**Frontend pattern for downloads:**
```js
const response = await fetch(`/api/v1/pr-attachments/${id}/download`, {
  headers: { Authorization: `Bearer ${token}` }
});
const blob = await response.blob();
const url = URL.createObjectURL(blob);
// open in new tab or trigger <a download>
```

PDF endpoints return `Content-Type: application/pdf` and `Content-Disposition: inline; filename="..."`.

---

## 9. Resource Shapes

All resource shapes are defined in `app/Http/Resources/`. Relationships are lazy — they only appear in the response when the server has loaded them (use `whenLoaded`). The presence of a related object key in the JSON does not mean it is always populated.

### UserResource
```jsonc
{
  "id": 1,
  "first_name": "Juan",
  "middle_name": "Santos",
  "last_name": "dela Cruz",
  "extension_name": null,
  "email": "juan@example.com",
  "is_active": true,
  "role_id": 2,
  "office_id": 1,
  "role": { /* RoleResource — loaded on me / show */ },
  "office": { /* OfficeResource — loaded on me / show */ },
  "created_at": "2026-06-25T01:00:00.000000Z",
  "updated_at": "2026-06-25T01:00:00.000000Z"
}
```

### PurchaseRequestResource
```jsonc
{
  "id": 1,
  "rf_number": "RF-2026-00001",        // null until submitted
  "pr_number": "PR-2026-00001",        // null until pr_prepared
  "requester_id": 3,
  "requesting_office_id": 2,
  "category_id": 1,
  "purpose": "Office supplies for Q3",
  "status": "submitted",
  "alobs_number": null,
  "total_amount": "12500.00",
  "submitted_at": "2026-06-26T08:30:00.000000Z",
  "requires_philgeps": false,
  "requester": { /* UserResource */ },
  "requesting_office": { /* OfficeResource */ },
  "category": { /* CategoryResource */ },
  "items": [ /* PurchaseRequestItemResource[] */ ],
  "attachments": [ /* PrAttachmentResource[] */ ],
  "created_at": "...",
  "updated_at": "..."
}
```

### RfqResource
```jsonc
{
  "id": 1,
  "rfq_number": "RFQ-...",
  "purchase_request_id": 1,
  "prepared_by_id": 4,
  "deadline": "2026-07-10",
  "status": "open",
  "prepared_by": { /* UserResource */ },
  "items": [ /* RfqItemResource[] */ ],
  "created_at": "...",
  "updated_at": "..."
}
```
> `file_path` is excluded — access RFQ files via an authorized download route.

### PurchaseOrderResource
```jsonc
{
  "id": 1,
  "po_number": "PO-...",
  "purchase_request_id": 1,
  "prepared_by_id": 4,
  "supplier_name": "ABC Trading",
  "supplier_address": "123 Main St, Manila",
  "delivery_terms": "Within 30 days",
  "payment_terms": "Net 30",
  "delivery_date": "2026-08-01",
  "total_amount": "12500.00",
  "status": "pending",
  "prepared_by": { /* UserResource */ },
  "items": [ /* PurchaseOrderItemResource[] */ ],
  "created_at": "...",
  "updated_at": "..."
}
```
> `signed_po_path` is excluded — access via `/download/purchase-order`.

### SupplierResource
```jsonc
{
  "id": 1,
  "name": "ABC Trading Corp",
  "tin_number": "123-456-789-000",
  "category_id": 2,
  "website": "https://abctrading.ph",
  "tags": ["hardware", "electronics"],
  "contact_person": "Maria Santos",
  "email": "maria@abctrading.ph",
  "phone": "+63 2 1234 5678",
  "address_street": "123 Rizal Ave",
  "address_city": "Manila",
  "address_province": "Metro Manila",
  "address_zip": "1000",
  "on_time_delivery_rate": 95.5,
  "defect_rate": 0.5,
  "is_active": true,
  "logo_url": "http://host/api/v1/suppliers/1/logo",  // null if no logo
  "category": { /* CategoryResource */ },
  "documents": [ /* SupplierDocumentResource[] */ ],
  "created_at": "...",
  "updated_at": "..."
}
```
> `logo_path` is excluded from the JSON. Use `logo_url` — it's the authenticated download route.

### NotificationResource
```jsonc
{
  "id": 1,
  "user_id": 3,
  "purchase_request_id": 5,
  "type": "status_change",
  "title": "Purchase Request Submitted",
  "message": "Your purchase request RF-2026-00001 has been submitted.",
  "is_read": false,
  "read_at": null,
  "created_at": "..."
}
```

### AbstractOfQuotationResource
```jsonc
{
  "id": 1,
  "rfq_id": 2,
  "prepared_by_id": 4,
  "recommended_supplier": "ABC Trading Corp",
  "recommended_amount": "11800.00",
  "status": "approved",
  "approved_at": "2026-07-15T10:00:00.000000Z",
  "prepared_by": { /* UserResource */ },
  "created_at": "...",
  "updated_at": "..."
}
```

### BacResolutionResource
```jsonc
{
  "id": 1,
  "resolution_number": "BAC-2026-001",
  "abstract_of_quotation_id": 1,
  "prepared_by_id": 4,
  "issued_at": "2026-07-20T00:00:00.000000Z",
  "prepared_by": { /* UserResource */ },
  "created_at": "...",
  "updated_at": "..."
}
```

### NoticeOfAwardResource
```jsonc
{
  "id": 1,
  "noa_number": "NOA-2026-001",
  "bac_resolution_id": 1,
  "awarded_supplier": "ABC Trading Corp",
  "awarded_amount": "11800.00",
  "issued_at": "2026-07-22T00:00:00.000000Z",
  "created_at": "...",
  "updated_at": "..."
}
```

---

## 10. App Layer Map

Understanding the request lifecycle helps when debugging or adding new features.

```
HTTP Request
  └─► routes/api.php              Route matched, middleware applied (auth:sanctum)
        └─► FormRequest            authorize() → policy check; rules() → validation
              └─► Controller        Thin — calls service, wraps response in envelope
                    └─► Service     Business logic, DB transactions, audit logging
                          └─► Model / Eloquent   Data access
                                └─► Resource      Shapes the JSON output
```

**Key patterns:**
- Controllers are `final` and thin — no business logic lives in them.
- Services hold all logic. Every mutation is wrapped in `DB::transaction()`.
- `AuditLogger::log()` / `AuditLogger::logMany()` write to `audit_logs` on every create/update/delete.
- `PrStatusHistoryService::record()` appends an immutable row on every PR status change.
- `NotificationService::notifyStatusChange()` dispatches in-system notifications on PR transitions.
- Policies are registered in `AppServiceProvider` and enforced via `$this->authorize()` in controllers.
- `InvalidStatusTransitionException` bubbles up from the service to `bootstrap/app.php` which converts it to a 422 JSON response.

---

*Last updated: 2026-06-30*
