---
name: ppas-policies
description: Role-based authorization policies for PPAS — which roles can do what on each model
metadata:
  type: project
---

21 Policy classes in `app/Policies/` (includes `SupplierPolicy` + `SupplierDocumentPolicy`), all registered via `Gate::policy()` in `AppServiceProvider::boot()`.

**Permission matrix by role (`role.name`) — 4-role model:**

| Resource | requester | procurement_officer | bac_secretariat | budget_officer |
|---|---|---|---|---|
| Role, Office, Category | view only | full CRUD | view only | view only |
| User | view only | full CRUD | view only | view only |
| PurchaseRequest | create + view/update/delete own `draft`; update own `returned` (resubmit) | full CRUD | view all + update (transition-gated) | view all + update (transition-gated) |
| PurchaseRequestItem, PrAttachment | create + manage on own `draft` PRs | full CRUD | view only | view only |
| PrStatusHistory | view only | view only | view only | view only |
| PurchaseOrder, PurchaseOrderItem | view only | full CRUD | view only | view only |
| Rfq, RfqItem, CanvassResponse, AbstractOfQuotation | view only | full CRUD | view only | view only |
| BacResolution, NoticeOfAward | view only | **view only** | **full CRUD** | view only |
| Notification | view + mark-read own | view all + mark-read | view + mark-read own | view + mark-read own |
| AuditLog | DENIED | view only | DENIED | view only |
| LoginLog | DENIED | view only | DENIED | DENIED |

**Key implementation notes:**
- `PurchaseRequestPolicy::create()` — `requester` or `procurement_officer`.
- `PurchaseRequestPolicy::update()` — `procurement_officer` unrestricted; `budget_officer` / `bac_secretariat` allowed at policy level, real gate is `PurchaseRequestTransitions::ROLE_MAP`; `requester` only own PRs in `draft` or `returned`.
- `PurchaseRequestPolicy::delete()` — `requester` can only delete own PRs with status `draft`.
- `BacResolutionPolicy` / `NoticeOfAwardPolicy` — `create`/`update`/`delete` are `bac_secretariat` **only** (this write access moved off `procurement_officer` in the role split).
- `PurchaseRequestTransitions::ROLE_MAP` — `bac_secretariat` owns `submitted:under_review`, `submitted:returned`, `under_review:for_budget_approval`, `under_review:returned`, `abstract_prepared:bac_resolution_noa`; `budget_officer` owns the `for_budget_approval` / `budget_approved` transitions; `procurement_officer` owns `forwarded_to_ppu:pr_prepared` onward; `requester` owns `draft:submitted` and `returned:submitted`.
- `NotificationPolicy::update()` — controls the `markRead` action (PATCH).
- `PurchaseRequestItemPolicy` / `PrAttachmentPolicy` — `view` also allows `budget_officer` + `bac_secretariat`; ownership checks read `$item->purchaseRequest?->requester_id`.
- `AuditLogPolicy` — `procurement_officer` + `budget_officer` only. `LoginLogPolicy` — `procurement_officer` only.

**Wiring:**
- Store Form Requests: `$this->user()->can('create', ModelClass::class)`
- Update Form Requests: `$this->user()->can('update', $this->route('param'))`
- Controllers: `$this->authorize('viewAny', Model::class)` in index; `$this->authorize('view', $model)` in show; `$this->authorize('delete', $model)` in destroy.

**Why:** Blueprint security.md requires role-based access control on all endpoints.
**How to apply:** When adding new models or endpoints, define a Policy class, register it in AppServiceProvider, and wire it into both Form Requests and the controller.

See [[ppas-architecture]] for the domain groupings.
