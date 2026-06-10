---
name: open-admin-fork
description: Repository-specific navigation skill for this OpenAdmin Laravel admin fork. Use when working anywhere in this package, including code changes, code review, debugging, refactoring, tests, docs, assets, forms, grids, actions, auth, install, config, or release/version tasks.
---

# OpenAdmin Fork

## First Read

Treat this repository as a Laravel admin package, not as a normal Laravel application.

- `AGENTS.md` contains Laravel Boost rules, but Boost MCP is not available here.
- `AGENTS.md` describes a Laravel 12 application context, while `composer.json` describes a package named `open-admin-org/open-admin`.
- Prefer local repository evidence over upstream OpenAdmin or Laravel-admin assumptions.
- Check `git status --short` before edits and do not modify unrelated dirty files.
- This skill bundle lives under `agents/`.

## Skill Map

Start with this skill, then load the narrow skill that matches the task:

- Package, compatibility, service provider, commands, tests, style, release: `./open-admin-package/SKILL.md`
- Forms, fields, validation, save pipeline, resettable fields, nested forms, cascades: `./open-admin-forms/SKILL.md`
- Grids, filters, columns, displayers, row actions, batch actions, restore: `./open-admin-grids-actions/SKILL.md`
- Blade views, JavaScript runtime, PJAX, assets, SCSS, custom alerts/prompts: `./open-admin-frontend-assets/SKILL.md`
- Auth, permissions, menu schema, install, migrations, config defaults: `./open-admin-auth-install/SKILL.md`
- General Laravel backend practices still apply when the session exposes `laravel-best-practices`.

## Repository Shape

- PHP namespace: `OpenAdmin\Admin\` mapped to `src/`.
- Package provider: `src/AdminServiceProvider.php`.
- Public facade alias: `OpenAdmin\Admin\Facades\Admin`.
- Config source: `config/admin.php`.
- Package migration: `database/migrations/2016_01_04_173148_create_admin_tables.php`.
- Views: `resources/views`, loaded as the `admin::` namespace.
- Vendored frontend assets: `resources/assets`.
- Tests: `tests`, using BrowserKit and a test Laravel skeleton under `vendor/laravel/laravel`.

## Fork Awareness

The fork has many local changes versus the local `main` branch. Do not assume original OpenAdmin behavior without checking current code.

Useful local comparisons:

```powershell
git diff --stat main..dev -- composer.json config/admin.php src resources tests database
git diff --name-status main..dev -- src resources/views resources/assets/open-admin
git log --oneline --no-merges main..dev -- src resources config composer.json
```

If `main` or `dev` is not present in a future checkout, inspect available branches with:

```powershell
git branch --all --verbose --no-abbrev
```

## Default Workflow

1. Identify the touched subsystem.
2. Load the subsystem skill from the map above.
3. Read current files and sibling implementations before changing anything.
4. Keep package compatibility and old OpenAdmin conventions in mind.
5. Change PHP, Blade, JS, SCSS, translations, config, and tests together when behavior crosses layers.
6. Run the smallest meaningful verification available in this package.

## Avoid

- Do not use Laravel Boost MCP instructions mechanically; the MCP is unavailable.
- Do not replace package conventions with Laravel 12 application skeleton conventions.
- Do not add new base directories without approval.
- Do not publish migrations as a default install strategy.
- Do not assume npm/Vite exists; this repository has no `package.json`.
