---
name: ppas-architecture
description: Core architecture decisions and directory layout for the PPAS API (Procurement Process Automation System for NIA Caraga Regional Office)
metadata:
  type: project
---

This is a Laravel 13 REST API for the Procurement Process Automation System (PPAS) at NIA Caraga Regional Office.

**Stack:** PHP 8.3+, Laravel 13, MySQL, Sanctum (token-based auth), PHPUnit 12 (Pest incompatible with PHPUnit ^12.5.12 as of 2026-06-25 — use plain PHPUnit feature tests).

**API versioning:** All endpoints under `/api/v1/` prefix. Routes registered in `routes/api.php` with `apiPrefix: 'api'` in `bootstrap/app.php`.

**Controller domains (grouped under `app/Http/Controllers/V1/`):**
- `Auth/` — UserController
- `Config/` — RoleController, OfficeController, CategoryController
- `Procurement/` — PurchaseRequestController, PurchaseRequestItemController, PrAttachmentController, PrStatusHistoryController, PurchaseOrderController, PurchaseOrderItemController
- `RFQ/` — RfqController, RfqItemController, CanvassResponseController, AbstractOfQuotationController
- `Resolution/` — BacResolutionController, NoticeOfAwardController
- `Monitoring/` — NotificationController, AuditLogController, LoginLogController

**Models use `$fillable` / `$hidden` array properties** (not the `#[Fillable]` / `#[Hidden]` PHP-8 attributes — converted 2026-08-31 to match the ICT blueprint `backend-laravel.md` / `security.md`, which mandate `$fillable` and prohibit `$guarded`). `casts()` method, relationships, and scopes as normal. Auto-generated identifiers are kept out of `$fillable` and set via `forceFill()` in services.

**Auto-generated fields (never accept from user input):**
- `rf_number`, `pr_number` on PurchaseRequest
- `po_number` on PurchaseOrder
- `rfq_number` on Rfq

**Read-only (append-only) models — index+show only, no write endpoints:**
- PrStatusHistory (`pr_status_histories` table, no `updated_at`)
- AuditLog (`audit_logs` table, no `updated_at`)
- LoginLog (`login_logs` table, no `updated_at`)

**Notification special rules:** No store/update. Only index, show, and PATCH `mark-read` action.

**File path fields:** Excluded from API Resource output (serve via authorized download routes, not public disk).

**Policy authorization pattern:**
- Base `Controller.php` uses `AuthorizesRequests` trait — `$this->authorize()` works in all controllers.
- Policies registered in `AppServiceProvider::boot()` via `Gate::policy(Model::class, PolicyClass::class)`.
- Form Requests `authorize()` handles `create` (store) and `update` checks.
- Controllers call `$this->authorize('viewAny', Model::class)` in `index()`, `$this->authorize('view', $model)` in `show()`, and `$this->authorize('delete', $model)` in `destroy()`.

**Four roles (name values in DB):** `requester`, `procurement_officer`, `bac_secretariat`, `budget_officer`. `bac_secretariat` (BAC Secretariat) was split out of `procurement_officer` after the initial build: it owns the completeness-review transitions (`submitted → under_review`, `→ returned`, `under_review → for_budget_approval`, `under_review → returned`, `abstract_prepared → bac_resolution_noa`) and is the only role that can author BAC Resolutions and Notices of Award. `procurement_officer` keeps PR/RFQ/PO preparation and the `forwarded_to_ppu → … → completed` transitions. Source of truth: [[ppas-policies]] and `app/Support/PurchaseRequestTransitions.php`.

**Test database:** Uses `ppas_test` MySQL database (not SQLite — pdo_sqlite driver is not installed on this server). phpunit.xml configured to use MySQL with DB_DATABASE=ppas_test. Tests use `RefreshDatabase` + seed `RoleSeeder` and `OfficeSeeder` in the base `TestCase::setUp()`.

**Why:** Blueprint compliance — api-contract.md, backend.md, security.md all require authorized file serving and immutable audit trails.

**How to apply:** When adding new endpoints or models, check if they fall into read-only or restricted-write categories.
