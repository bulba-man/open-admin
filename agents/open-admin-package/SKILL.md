---
name: open-admin-package
description: Package-level workflow and conventions for this OpenAdmin fork. Use for composer metadata, service provider changes, package compatibility, artisan commands, test setup, style, release/version edits, dependency decisions, repository analysis, or any task that needs package context rather than Laravel app context.
---

# OpenAdmin Package

## Package Facts

- Package name: `open-admin-org/open-admin`.
- Type: Composer library.
- Autoload: `OpenAdmin\Admin\` from `src/`, plus `src/helpers.php`.
- Service provider: `OpenAdmin\Admin\AdminServiceProvider`.
- Facade alias: `Admin` to `OpenAdmin\Admin\Facades\Admin`.
- Main install command: `php artisan admin:install`.
- Asset/config publish command: `php artisan vendor:publish --provider="OpenAdmin\Admin\AdminServiceProvider"`.

## Compatibility Reality

Use `composer.json` as the declared compatibility contract, but verify against current code.

- Declared PHP constraint is `^8.1`.
- Declared Laravel framework constraint is `^10.0|^11.0|^12.0`.
- Current source already contains PHP 8-era syntax in places, including typed properties.
- Do not add newer syntax or APIs casually. First decide whether the task targets the declared package contract or the current fork runtime.
- Prefer patterns already present in sibling files over generalized Laravel 12 advice.

## Laravel Boost Constraint

`AGENTS.md` asks for Laravel Boost MCP and `search-docs`, but this repository does not have Boost MCP available.

Use local sources instead:

- `composer.json` for supported packages and versions.
- Current package code in `src`, `config`, `database`, `resources`, and `tests`.
- Artisan commands only when a host Laravel test app and dependencies are installed.
- Official docs only if the user explicitly allows or asks for current external docs.

## Service Provider Rules

When editing `src/AdminServiceProvider.php`:

- Keep view namespace `admin::` from `resources/views`.
- Publishing tags are `open-admin-config`, `open-admin-lang`, `open-admin-assets`, and `open-admin-test`.
- The provider does not publish package migrations.
- Middleware aliases include `admin.auth`, `admin.throttle`, `admin.pjax`, `admin.log`, `admin.permission`, `admin.bootstrap`, and `admin.session`.
- The `admin` middleware group includes auth, throttle, pjax, log, bootstrap, and permission.
- Router macros `content` and `component` are registered in `macroRouter()`.

## Command Rules

Command classes live in `src/Console`.

- `InstallCommand` runs package migrations from `database/migrations` with `--realpath`.
- `InstallCommand` seeds only when the configured admin user model is empty.
- `PublishCommand` delegates to `vendor:publish` and clears views.
- Generator commands use stubs in `src/Console/stubs`.
- Keep command signatures stable unless the user requested a breaking change.

## Testing

This package uses old-style PHPUnit and BrowserKit tests, not `php artisan test`.

- Test command from `composer.json`: `composer test`.
- Direct command: `vendor/bin/phpunit`.
- PHPUnit config: `phpunit.xml.dist`.
- Legacy BrowserKit tests expect a Laravel skeleton at `vendor/laravel/laravel/bootstrap/app.php`.
- Narrow package-level tests may extend `Orchestra\Testbench\TestCase` directly when they only need package service provider bootstrapping.
- Tests configure MySQL via `MYSQL_HOST`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`.
- `tests/TestCase.php` publishes package assets/config, runs `admin:install`, migrates test tables, loads routes, then tears tables down.

Prefer targeted tests:

```powershell
vendor\bin\phpunit tests\UserFormTest.php
vendor\bin\phpunit --filter testName
composer test
```

If dependencies or the test Laravel skeleton are missing, state that verification is blocked rather than inventing app-level checks.

## Style

- `.styleci.yml` uses the recommended preset and disables `unalign_equals`.
- Laravel Pint is installed as a dev dependency.
- Run `vendor/bin/pint --dirty --format agent` after editing PHP files.
- Keep existing PHPDoc-heavy style and legacy-compatible method signatures unless nearby code clearly uses types.
- Avoid broad dependency changes without approval.
- Keep generated CSS and source SCSS in sync when changing styles.

## Frontend Tooling

- There is no `package.json`.
- JavaScript and library assets are vendored under `resources/assets`.
- Composer has a `sass` script that watches `resources/assets/open-admin/scss/styles.scss` and page SCSS into committed CSS.
- Do not recommend Vite or npm build steps unless the repository later adds them.

## Release Metadata

- `composer.json` contains an explicit `version`.
- `src/Admin.php` contains `Admin::VERSION`.
- These can diverge. If changing a release version, inspect both and ask if only one should change.
