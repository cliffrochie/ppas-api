# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# First-time setup
composer run setup

# Start all dev services concurrently (HTTP server, queue worker, log viewer, Vite)
composer run dev

# Run all tests
composer run test
# or directly:
php artisan test

# Run a single test file
php artisan test tests/Feature/ExampleTest.php

# Run a specific test by name
php artisan test --filter=test_name

# Code style (Laravel Pint)
./vendor/bin/pint
./vendor/bin/pint --test   # dry-run, no changes

# Interactive REPL
php artisan tinker

# Tail logs (alternative to Pail in the dev script)
php artisan pail
```

## Architecture

**Stack:** PHP 8.3+, Laravel 13.x, SQLite (default for local/test), PHPUnit 12, Pint (code style), Pail (log viewer), Vite.

**API-first design:** `bootstrap/app.php` already configures all `api/*` routes to return JSON error responses — no additional exception handling setup needed for API routes.

**No API routes file yet:** Only `routes/web.php` and `routes/console.php` exist. When adding API endpoints, register `api: __DIR__.'/../routes/api.php'` in `bootstrap/app.php`'s `withRouting()` call and create `routes/api.php`.

**Models use PHP 8 attributes:** The `User` model uses `#[Fillable]` and `#[Hidden]` class-level attributes (Laravel 13 style) instead of `$fillable`/`$hidden` array properties. Follow this pattern for new models.

**Testing environment:** `phpunit.xml` configures an in-memory SQLite database (`DB_DATABASE=:memory:`) for all tests — no separate test database setup required.

**Queue and cache:** Default driver is `database` in `.env.example`. Tests override this to `sync` and `array` respectively via `phpunit.xml`.

**Dependency manager scripts:**
- `composer run setup` — full first-time bootstrap (install, key gen, migrate, npm install + build)
- `composer run dev` — orchestrates server + queue + pail + vite via `concurrently`
- `composer run test` — clears config cache before running PHPUnit

## Subagent Delegation

Before starting any task in this project, check whether a specialized subagent (via the Agent tool) is qualified to handle it. If a good match exists, delegate the task to it instead of doing the work directly.

Agents relevant to this repo:
- `laravel-backend-engineer` — building or reviewing backend Laravel code: endpoints, validation, migrations, directory structure
- `bug-fixer` — debugging issues across backend, auth, uploads, routing
- `pr-blueprint-reviewer` / `spec-blueprint-reviewer` — reviewing code or specs against the ICT Information System Blueprint
- `pr-description-writer` — writing PR descriptions, release notes, hotfix summaries
- `delivery-manager` — turning requirements into scoped implementation plans, acceptance criteria, progress reports
- `Explore` — open-ended codebase search spanning multiple files or unclear locations
- `general-purpose` — fallback for anything that doesn't fit a specialized agent above

Skip delegation only when no agent's description is a genuine match, or the task is trivial enough (single-line fix, quick lookup) that spinning one up adds no value.

## Karpathy-Inspired Guidelines

Behavioral guidelines to reduce common LLM coding mistakes, derived from [Andrej Karpathy's observations](https://x.com/karpathy/status/2015883857489522876) on LLM coding pitfalls. Source: https://github.com/multica-ai/andrej-karpathy-skills. Bias toward caution over speed on non-trivial work — use judgment on trivial tasks (typo fixes, obvious one-liners).

### 1. Think Before Coding

**Don't assume. Don't hide confusion. Surface tradeoffs.**

- State your assumptions explicitly. If uncertain, ask.
- If multiple interpretations exist, present them — don't pick silently.
- If a simpler approach exists, say so. Push back when warranted.
- If something is unclear, stop. Name what's confusing. Ask.

### 2. Simplicity First

**Minimum code that solves the problem. Nothing speculative.**

- No features beyond what was asked.
- No abstractions for single-use code.
- No "flexibility" or "configurability" that wasn't requested.
- No error handling for impossible scenarios.
- If you write 200 lines and it could be 50, rewrite it.

Ask yourself: "Would a senior engineer say this is overcomplicated?" If yes, simplify.

### 3. Surgical Changes

**Touch only what you must. Clean up only your own mess.**

When editing existing code:
- Don't "improve" adjacent code, comments, or formatting.
- Don't refactor things that aren't broken.
- Match existing style, even if you'd do it differently.
- If you notice unrelated dead code, mention it — don't delete it.

When your changes create orphans:
- Remove imports/variables/functions that YOUR changes made unused.
- Don't remove pre-existing dead code unless asked.

The test: every changed line should trace directly to the user's request.

### 4. Goal-Driven Execution

**Define success criteria. Loop until verified.**

Transform tasks into verifiable goals:
- "Add validation" → "Write tests for invalid inputs, then make them pass"
- "Fix the bug" → "Write a test that reproduces it, then make it pass"
- "Refactor X" → "Ensure tests pass before and after"

For multi-step tasks, state a brief plan:
```
1. [Step] → verify: [check]
2. [Step] → verify: [check]
3. [Step] → verify: [check]
```

Strong success criteria let you loop independently. Weak criteria ("make it work") require constant clarification.
