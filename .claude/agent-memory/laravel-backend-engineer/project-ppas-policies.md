---
name: ppas-policies
description: Role-based authorization policies for PPAS — which roles can do what on each model
metadata:
  type: project
---

19 Policy classes in `app/Policies/`, all registered via `Gate::policy()` in `AppServiceProvider::boot()`.

**Permission matrix by role (`role.name`):**

| Resource | requester | procurement_officer | budget_officer |
|---|---|---|---|
| Role, Office, Category | view only | full CRUD | view only |
| User | view only | full CRUD | view only |
| PurchaseRequest | create + view/edit/delete own drafts | full CRUD | view + update (ALOBS) |
| PurchaseRequestItem, PrAttachment | create + manage items on own draft PRs | full CRUD | view only |
| PrStatusHistory | view only | view only | view only |
| PurchaseOrder, PurchaseOrderItem | view only | full CRUD | view only |
| Rfq, RfqItem, CanvassResponse, AbstractOfQuotation | view only | full CRUD | view only |
| BacResolution, NoticeOfAward | view only | full CRUD | view only |
| Notification | view + mark-read own only | view all + mark-read | view own only |
| AuditLog | DENIED | view only | view only |
| LoginLog | DENIED | view only | DENIED |

**Key implementation notes:**
- `PurchaseRequestPolicy::update()` — requester can only update own PRs with status `draft`.
- `PurchaseRequestPolicy::delete()` — requester can only delete own PRs with status `draft`.
- `NotificationPolicy::update()` — controls the `markRead` action (PATCH).
- `PurchaseRequestItemPolicy` and `PrAttachmentPolicy` — check `$item->purchaseRequest?->requester_id`.

**Wiring:**
- Store Form Requests: `$this->user()->can('create', ModelClass::class)`
- Update Form Requests: `$this->user()->can('update', $this->route('param'))`
- Controllers: `$this->authorize('viewAny', Model::class)` in index; `$this->authorize('view', $model)` in show; `$this->authorize('delete', $model)` in destroy.

**Why:** Blueprint security.md requires role-based access control on all endpoints.
**How to apply:** When adding new models or endpoints, define a Policy class, register it in AppServiceProvider, and wire it into both Form Requests and the controller.

See [[ppas-architecture]] for the domain groupings.
