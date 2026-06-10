---
name: open-admin-frontend-assets
description: Frontend runtime, Blade layout, vendored assets, SCSS, PJAX, AJAX navigation, CSRF refresh, custom alerts/prompts, resettable JavaScript, and Bootstrap 5 behavior for this OpenAdmin fork. Use when changing resources/views, resources/assets, layout scripts, JS helpers, CSS, SCSS, asset publishing, or UI behavior.
---

# OpenAdmin Frontend Assets

## Asset Model

This repository vendors frontend dependencies.

- Main package assets live in `resources/assets`.
- OpenAdmin runtime JS lives in `resources/assets/open-admin/js`.
- OpenAdmin SCSS lives in `resources/assets/open-admin/scss`.
- Compiled CSS is committed under `resources/assets/open-admin/css`.
- Views live in `resources/views` and are loaded as `admin::`.
- There is no `package.json`, Vite config, or npm build pipeline.

Composer has a Sass watcher:

```powershell
composer run sass
```

That command expects a `sass` executable and writes compressed CSS from `resources/assets/open-admin/scss/styles.scss` and page SCSS.

## Publishing

Assets are published by `AdminServiceProvider::registerPublishing()`:

- `resources/assets` to `public/vendor/open-admin` with tag `open-admin-assets`.
- `resources/assets/test` to `public/vendor/open-admin-test` with tag `open-admin-test`.

Do not add npm-only build advice unless the repository later gains npm tooling.

## Layout Runtime

Main layout: `resources/views/index.blade.php`.

Important layout contracts:

- `LA.token` is initialized from `csrf_token()`.
- `LA.refresh_csrf_url` uses route `refresh-csrf`.
- `LA.refresh_csrf_interval` uses `config('admin.auth.refresh_csrf_interval', 600)`.
- `LA.user` is JSON for the current admin user.
- PJAX content is bounded by `<!--start-pjax-container-->` and `<!--end-pjax-container-->`.
- Dynamic style, HTML, and script stacks are emitted inside the PJAX container.
- Custom alert and prompt modals are included based on config flags.

If changing PJAX extraction, update `src/Middleware/Pjax.php` and layout markers together.

## Core JavaScript

Main runtime: `resources/assets/open-admin/js/open-admin.js`.

Core objects:

- `admin.ajax`
- `admin.pages`
- `admin.form`
- `admin.grid`
- `admin.action`

Fork-added lifecycle events:

- `adminRequestFinal`
- `adminMenuInit`
- `adminMenuInited`
- `adminAjaxInit`
- `adminAjaxInited`
- `adminPagesInit`
- `adminPagesInited`

Use these events for extension hooks instead of patching core init code when practical.

## AJAX And PJAX

`admin.ajax` intercepts internal links and pjax forms.

- Links with class `no-ajax` bypass AJAX navigation.
- External URLs and `_blank` targets are not intercepted.
- `admin.ajax.navigate()` updates history and active menu state.
- `admin.ajax.request()` starts NProgress, calls Axios, dispatches `adminRequestFinal`, then reinitializes pages.
- `admin.ajax.done()` replaces the main content and evals inline scripts from the response.
- GET form submission merges existing URL query parameters that are missing from the new form data.

When adding page scripts, make them safe to eval after PJAX reloads and avoid binding duplicate handlers.

## CSRF Refresh

CSRF refresh is a fork feature.

- Route is registered in `Admin::routes()` as `GET refresh-csrf`, name `refresh-csrf`.
- Route middleware is `web` and `admin.auth`.
- Runtime calls `admin.ajax.refreshCsrfToken()` on an interval.
- Refresh updates `LA.token`, all hidden `_token` inputs, and the csrf meta tag.

If changing auth route prefixes or layout token names, verify refresh still works.

## Forms Runtime

Form JS: `resources/assets/open-admin/js/open-admin-form.js`.

Important contracts:

- `admin.form.addAjaxSubmit()` binds forms with `pjax-container`.
- `admin.form.beforeSaveCallbacks` run before submission.
- Client validation uses `.needs-validation` and `.was-validated`.
- Tabs are reactivated from URL hash and errors mark their tabs.
- Cascade-hidden fields are disabled on submit through `disable_cascaded_forms()`.
- `admin.form.resettable()` initializes `.reset-field-to-default`.

When adding form widgets, ensure they reinitialize after `admin.pages.init()`.

## Resettable Runtime

Resettable JS: `resources/assets/open-admin/js/open-admin-resettable.js`.

Supported field families:

- ChoicesJS selects via `window.choices_vars`.
- Dual listbox via `window.listbox_vars`.
- Native select.
- Checkbox.
- Radio.
- Switch.
- Textarea.
- Input.

When adding a custom JS widget with resettable behavior, either make it look like a supported native field or extend `ResettableField`.

## Custom Alerts And Prompts

Config keys:

- `admin.use_custom_alerts`
- `admin.use_custom_prompts`

Files:

- `resources/views/partials/modal-alert.blade.php`
- `resources/views/partials/modal-prompt.blade.php`
- `resources/assets/open-admin/js/open-admin-prompt.js`

Behavior:

- Custom alert overrides `window.alert` and preserves `window.alert_native`.
- Passing `title === true` to alert calls native alert.
- Custom prompt initializes `promptShell` and overrides `window.prompt`, preserving `window.prompt_native`.
- Prompt supports title, content, value, validation, form/input attributes, and confirm/cancel/always callbacks.

Keep these overrides opt-in through config. Do not unconditionally replace native browser APIs.

## Bootstrap 5

The UI uses Bootstrap 5 assets and data attributes.

- Use `data-bs-toggle`, `data-bs-target`, and Bootstrap 5 JS APIs.
- Tooltip and popover initialization runs in both DOMContentLoaded and page init paths.
- Modal code should use `bootstrap.Modal.getOrCreateInstance()`.

Avoid Bootstrap 3/AdminLTE assumptions in new Blade or JS.

## Views

Common view areas:

- Main layout: `resources/views/index.blade.php`.
- Sidebar/menu: `resources/views/partials`.
- Forms: `resources/views/form`.
- Action modal fields: `resources/views/actions/form`.
- Grid: `resources/views/grid`.
- Filters: `resources/views/filter`.
- Components: `resources/views/components`.

When changing a field or grid feature, update all corresponding normal view, action view, JS, SCSS, and translations.

## Translations

Language files live in `resources/lang/*/admin.php`.

Fork-added strings include:

- `admin.choices.*`
- `admin.listbox.search_placeholder`
- `admin.css_classes`
- `admin.open_by_ajax`
- `admin.menu_form.slug_help`
- restore strings

At minimum, keep English and Russian in sync when adding user-facing labels because both are actively modified in the fork.
