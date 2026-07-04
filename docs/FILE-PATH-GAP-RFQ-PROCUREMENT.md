# Backend Gap: No Way to Populate `file_path` on RFQ / Abstract / BAC Resolution / NOA

**Found while:** wiring the `ppas-web` frontend to the RFQ, Abstract of Quotation, BAC Resolution, and Notice of Award endpoints.
**Status:** RESOLVED. Implemented Option 1 (dedicated upload + download endpoints, mirroring `PrAttachmentController`/`SupplierDocumentController`). See `docs/PROJECT.md` §5.4/§5.5/§8/§9 for the updated contract. All four Store/Update FormRequests now accept an optional/required `file` upload instead of a raw `file_path` string; each entity has a new `GET .../{id}/download` route; each Resource exposes a `download_url` field.

---

## The problem

`file_path` is a **required or optional string** in the Store/Update requests for all four entities, but the API gives the frontend **no way to obtain a valid value** for it.

| Entity | Store rule | Update rule |
|---|---|---|
| `Rfq` | `file_path` nullable string | nullable string |
| `AbstractOfQuotation` | `file_path` nullable string | nullable string |
| `BacResolution` | `file_path` **required** string | sometimes required string |
| `NoticeOfAward` | `file_path` **required** string | sometimes required string |

At the same time, every one of their Resources explicitly excludes `file_path` from the JSON response:

```php
// RfqResource / AbstractOfQuotationResource / BacResolutionResource / NoticeOfAwardResource
// file_path intentionally excluded — served via authorized download route
```

That comment describes the pattern used elsewhere in the app (`pr_attachments`, `supplier_documents`), where the same exclusion is paired with a dedicated download route:

```
GET /pr-attachments/{id}/download
GET /supplier-documents/{id}/download
```

**No equivalent route exists for `rfqs`, `abstracts-of-quotation`, `bac-resolutions`, or `notices-of-award`.** Confirmed via:

```
$ grep -n "download" routes/api.php | grep -i "rfq\|abstract\|bac.resolution\|notice"
# (no output)
```

The project's own `docs/PROJECT.md` §8 ("File Download Patterns") lists download endpoints for PR attachments, PR/PO PDFs, supplier logo, and supplier documents — it does not list one for any of these four entities, consistent with none existing.

## Why the frontend can't work around this

- `file_path` is a plain `fillable` column on all four models (`Rfq`, `AbstractOfQuotation`, `BacResolution`, `NoticeOfAward`) — nothing derives, generates, or defaults it server-side.
- No `Observer`, `Listener`, or `Job` in the codebase references any of these four models (checked `app/Observers`, `app/Listeners`, `app/Jobs`, `app/Events`).
- The services (`RfqService`, `AbstractOfQuotationService`, `BacResolutionService`, `NoticeOfAwardService`) pass `$validated` straight into `Model::create()` / `Model::update()` with no transformation.

So the only way the frontend could supply a real value is if some other endpoint returned one — and none does. Uploading a document as a `pr_attachment` (type `rfq` / `bac_resolution` / `noa`) doesn't help either, because `PrAttachmentResource` hides `file_path` too, exposing only a `download_url`, never the raw path.

**Net effect:** `BacResolution` and `NoticeOfAward` cannot currently be created at all through the API (their `file_path` is required and unobtainable), and `Rfq` / `AbstractOfQuotation` can only be created without a document attached.

## Recommended fixes (pick one)

1. **Add dedicated upload + download endpoints** for each of the four entities, mirroring `PrAttachmentController` / `SupplierDocumentController`. Most consistent with the existing pattern in the codebase; recommended if these documents are expected to be distinct uploads separate from `pr_attachments`.
2. **Derive `file_path` server-side** from an existing `pr_attachments` row instead of accepting it as input — e.g. look up the most recent attachment of the matching `type` (`rfq` / `bac_resolution` / `noa`) for the related purchase request, and drop `file_path` from the Store/Update validation rules entirely. Least new surface area if these documents are always uploaded via the existing PR attachment flow first.
3. **Expose `file_path` in the Resource response** (or a signed variant of it) so a value can at least round-trip once obtained some other way. This alone doesn't solve the "obtain a value in the first place" problem, so it would need to be paired with option 1 or 2.

## Affected endpoints

- `POST /rfqs`, `PATCH /rfqs/{id}`
- `POST /abstracts-of-quotation`, `PATCH /abstracts-of-quotation/{abstract_of_quotation}`
- `POST /bac-resolutions`, `PATCH /bac-resolutions/{id}` — **blocked**, `file_path` required
- `POST /notices-of-award`, `PATCH /notices-of-award/{notice_of_award}` — **blocked**, `file_path` required
