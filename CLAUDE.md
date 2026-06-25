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
