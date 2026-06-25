---
name: ppas-models
description: Model-to-table mapping and key relationships for PPAS API models
metadata:
  type: project
---

Key models and their tables for the PPAS project:

| Model | Table | Notes |
|---|---|---|
| Role | roles | name unique, max:100 |
| Office | offices | name max:255, code max:50 nullable |
| Category | categories | name max:100, is_active boolean |
| User | users | first/middle/last/extension_name, role_id FK, office_id FK nullable, is_active |
| PurchaseRequest | purchase_requests | 16-value status enum, rf_number/pr_number auto-generated |
| PurchaseRequestItem | purchase_request_items | child of PurchaseRequest |
| PrAttachment | pr_attachments | type enum: app_ppmp/signed_pr/rfq/bac_resolution/noa/other |
| PrStatusHistory | pr_status_histories | append-only, no updated_at |
| PurchaseOrder | purchase_orders | po_number auto-generated, status: draft/for_signature/signed/acknowledged/completed |
| PurchaseOrderItem | purchase_order_items | pr_item_id nullable FK |
| Rfq | rfqs | rfq_number auto-generated, status: draft/for_signature/signed/canvassing/closed |
| RfqItem | rfq_items | child of Rfq |
| CanvassResponse | canvass_responses | child of RfqItem |
| AbstractOfQuotation | abstracts_of_quotation | status: draft/approved, one-to-one with Rfq |
| BacResolution | bac_resolutions | one-to-one with AbstractOfQuotation |
| NoticeOfAward | notices_of_award | one-to-one with BacResolution |
| Notification | notifications | immutable, no updated_at |
| AuditLog | audit_logs | polymorphic (auditable_type/auditable_id), no updated_at |
| LoginLog | login_logs | status: success/failed/locked_out, no updated_at |

PurchaseRequest status enum values (16):
draft, submitted, under_review, returned, for_budget_approval, disapproved, budget_approved, forwarded_to_ppu, pr_prepared, pr_approved, rfq_prepared, canvassing, abstract_prepared, bac_resolution_noa, po_prepared, completed
