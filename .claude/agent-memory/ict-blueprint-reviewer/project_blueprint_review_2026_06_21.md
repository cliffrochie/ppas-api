---
name: blueprint-cross-document-review-2026-06-21
description: Findings from the first full cross-document consistency review of the ICT blueprint (all 9 files). 21 issues found — 4 HIGH, 12 MEDIUM, 5 LOW.
metadata:
  type: project
---

Full cross-document review conducted on 2026-06-21 against all 9 blueprint files.

**Why:** User requested a thorough consistency and contradiction audit before development begins on the ppas-api Laravel project.

**How to apply:** When reviewing new code or blueprint updates, check these known issues are not being built around incorrect assumptions. Flag any code that relies on the contradicted or ambiguous sections.

## Critical (HIGH severity) findings to carry forward

1. **Architecture contradiction**: `api-contract.md` diagram says Nginx; `devops.md` infra table says Windows IIS. Docker Compose uses Nginx. Deployment target is ambiguous — affects CORS header and CSP placement decisions.

2. **Push notification contradiction**: backend=Reverb, frontend=Laravel Echo, mobile=FCM. Never reconciled. Relationship between Reverb+Echo (web real-time) and FCM (mobile device push) is not documented.

3. **Refresh token undefined**: `security.md` says server issues refresh token + rotation required in production. No refresh endpoint defined anywhere. Client 401 behavior (clear auth + redirect) contradicts silent refresh intent. Sanctum PAT does not natively support refresh rotation.

4. **API Resources "optional" vs "MUST"**: Same file (`backend.md`) says Resources are "optional" in directory table and "never return raw models" in coding standards. Direct intra-file contradiction.

## Key MEDIUM findings

- `image/jpg` is not a valid MIME type — bug in `frontend.md` ALLOWED_MIME_TYPES example. Should be `image/jpeg`.
- Branch naming: `git.md` calls integration branch `development`; `devops.md` CI triggers on `develop`. One will not fire.
- Both `$fillable` and `$guarded` shown simultaneously in backend.md mass assignment example — mutually exclusive in Laravel.
- Mobile token storage says "secure mechanism" but never names `expo-secure-store` — `security.md` cross-reference to `mobile.md` is circular.
- Hook location ambiguous: guidance says `src/hooks/` OR `features/<domain>/hooks/` without clear rule for which to use.
- Testing stack split: `conventions.md` includes RTL and MSW; `frontend.md` stack table omits both.
- Mobile has no feature-module architecture; no explanation of intentional divergence from frontend Bulletproof React pattern.

## Links
- Full report returned as assistant message in conversation on 2026-06-21.
- [[blueprint-review-feedback]] — to be created when user responds with corrections or confirmations.
