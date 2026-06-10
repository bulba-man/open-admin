---
name: open-admin-auth-install
description: Auth, permissions, menu, install, migration, seeding, config defaults, and admin route behavior for this OpenAdmin fork. Use when changing admin auth models, HasPermissions, Permission middleware, menu fields, install command, admin database schema, seeder, config/admin.php, or route/middleware registration.
---

# OpenAdmin Auth Install

## Key Files

- Config: `config/admin.php`.
- Provider: `src/AdminServiceProvider.php`.
- Routes: `src/Admin.php`.
- Install command: `src/Console/InstallCommand.php`.
- Migration: `database/migrations/2016_01_04_173148_create_admin_tables.php`.
- Seeder: `src/Auth/Database/AdminTablesSeeder.php`.
- Auth models: `src/Auth/Database`.
- Permission middleware: `src/Middleware/Permission.php`.
- Menu controller: `src/Controllers/MenuController.php`.
- Menu view: `resources/views/partials/menu.blade.php`.

## Install Flow

This fork does not publish package migrations by default.

- `AdminServiceProvider::registerPublishing()` publishes config, lang, assets, and test assets only.
- `admin:install` calls `migrate` with `--path` pointing at the package migration directory and `--realpath => true`.
- `admin:install` seeds `AdminTablesSeeder` only when the configured users model has zero rows.
- `admin:install` then creates the configured admin directory and stubs.

Do not recommend migration publishing as the default fix unless the user explicitly asks for a different install model.

## Admin Routes

`Admin::routes()` registers built-in admin resources under `config('admin.route.prefix')` and `config('admin.route.middleware')`.

Built-in handler routes:

- `POST _handle_form_`
- `POST _handle_action_`
- `GET _handle_selectable_`
- `GET _handle_renderable_`

Fork-added route:

- `GET refresh-csrf`, name `refresh-csrf`, middleware `web` and `admin.auth`.

If changing route prefixes, verify JS `LA.refresh_csrf_url`, action URLs, selectable URLs, and permission pass-through rules.

## Config Defaults

Important config keys in `config/admin.php`:

- `auth.refresh_csrf_interval`
- `check_route_permission`
- `check_menu_roles`
- `use_custom_alerts`
- `use_custom_prompts`
- `menu_bind_permission`
- `grid_action_class`
- `database.*_table`
- `database.*_model`

Keep nested key paths exact. For example, CSRF refresh reads `admin.auth.refresh_csrf_interval`, not a root-level key.

## Database Schema

Fork schema additions in the package migration:

- `admin_roles.slug` unique.
- `admin_permissions.slug` unique.
- `admin_menu.slug` unique.
- `admin_menu.classes` nullable.
- `admin_menu.ajax` boolean default true nullable.
- `admin_menu.permission` nullable.

If changing schema, update all relevant places:

- Migration.
- Config expectations.
- Model fill/relationship behavior.
- Seeder.
- Controller forms.
- Views.
- Tests.

## Seeder

`AdminTablesSeeder` truncates and recreates default users, roles, permissions, and menus.

Default role:

- `administrator`

Default permissions include:

- `*`
- `dashboard`
- `auth.login`
- `auth.setting`
- `auth.management`

Default menu rows include slugs:

- `dashboard`
- `admin`
- `users`
- `roles`
- `permissions`
- `menu`
- `logs`

Do not run this seeder casually against production-like data. It truncates package auth tables.

## Permissions

`src/Auth/Database/HasPermissions.php` is forked.

- `isAdministrator()` returns true for roles `administrator`, `developer`, or `dev`.
- Direct user permission slugs are checked first.
- Role permission slugs are checked next.
- Hierarchical slug inheritance is supported: permission `auth` grants checks like `auth.users` because checked ability segments are intersected.
- Empty ability returns true.

`Permission::shouldPassThrough()` still checks HTTP method/path rules for route access.

`Permission` middleware pass-through includes:

- `auth/login`
- `auth/logout`
- `_handle_action_`
- `_handle_form_`
- `_handle_selectable_`
- `_handle_renderable_`

When adding new internal handler routes, decide whether they need pass-through or explicit permission checks.

## Menu Behavior

Menu UI combines roles and permission slugs.

- `MenuController` form fields include `slug`, `classes`, `ajax`, `roles`, and optional `permission`.
- `Menu::withPermission()` reads `admin.menu_bind_permission`.
- `resources/views/partials/menu.blade.php` renders a menu item only when the current admin user is visible for roles and can the menu permission.
- `ajax` false adds class `no-ajax` so the JS router does not intercept the link.
- `classes` are applied to menu `<li>`.
- External URLs open with `target="_blank"`.

When editing menu data, keep both DB rows and rendered menu behavior in sync.

## Role And Permission Models

- `Role` has fillable `name` and `slug`.
- `Permission` has fillable `name`, `slug`, `http_method`, and `http_path`.
- Deleting roles detaches administrators and permissions.
- Deleting permissions detaches roles.
- `Menu` detaches roles on delete.

Use configured model classes and table names from `config('admin.database.*')` rather than hardcoding defaults.

## Middleware Order

The provider registers the `admin` middleware group in this order:

1. `admin.auth`
2. `admin.throttle`
3. `admin.pjax`
4. `admin.log`
5. `admin.bootstrap`
6. `admin.permission`

Be careful changing order. `admin.bootstrap` loads package bootstrap and assets, while `admin.permission` expects an authenticated admin user.
