---
name: open-admin-grids-actions
description: Grid, filter, displayer, row action, batch action, selectable, and restore behavior for this OpenAdmin fork. Use when changing or reviewing Grid, Grid Model, Grid Column, filters, displayers, actions, inline edit, batch tools, soft delete restore, or action JavaScript responses.
---

# OpenAdmin Grids And Actions

## Key Files

- Grid core: `src/Grid.php`.
- Grid model wrapper: `src/Grid/Model.php`.
- Columns: `src/Grid/Column.php` and `src/Grid/Column`.
- Filters: `src/Grid/Filter.php` and `src/Grid/Filter`.
- Displayers: `src/Grid/Displayers`.
- Row actions: `src/Actions/RowAction.php`, `src/Grid/Displayers/Actions/Actions.php`.
- Batch actions: `src/Actions/BatchAction.php`, `src/Grid/Tools/BatchActions.php`.
- Action responses: `src/Actions/Response.php`.
- Action JS: `resources/assets/open-admin/js/open-admin-actions.js`.

## Grid Core Changes

Preserve fork-specific grid data behavior.

- `Grid::headerDark()` sets option `header_dark`.
- `Grid::build()` no longer uses raw `$collection->toArray()` only.
- Each row data array receives `raw_model` with the original Eloquent model instance.
- Row callbacks and action displayers may depend on that raw model being available.
- `fixedFooter` defaults to true.

When changing `Grid::build()` or row construction, verify:

- Displayers that expect arrays.
- Actions that expect Eloquent rows.
- Inline edit responses.
- Exporters and total rows.

## Columns And Header Filters

`src/Grid/Column.php` includes alignment helpers:

- `textAlign($align)`
- `textCenter()`
- `textLeft()`
- `textRight()`

Column filter additions include:

- `src/Grid/Column/RadioFilter.php`
- Existing `CheckFilter`, `InputFilter`, and `RangeFilter`

`RadioFilter` renders a dropdown radio list and submits via GET/PJAX. If changing it, keep Bootstrap 5 attributes and the all-option behavior.

## Grid Filters

`src/Grid/Filter/AbstractFilter.php` has layout width controls:

- `setWidth($field = 8, $label = 2)`
- `protected array $width`
- `protected $inline = true`

`src/Grid/Filter/Between.php` is forked:

- It supports `setPlaceholders($start, $end)`.
- It can tolerate scalar input by treating it as start value.
- `datetime($options = [])` accepts an optional `icon`.
- Datetime setup uses `Admin::makeFlatpickrInit()` so Flatpickr plugins can be passed.

Filter presenter changes:

- `Presenter::setWidth()` style patterns may be used by views.
- `Presenter\Select` supports native and empty-option behavior.
- ChoicesJS translations come from `admin.choices.*`.

When changing filters, inspect both `src/Grid/Filter/*` and `resources/views/filter/*`.

## Row Actions

`src/Actions/RowAction.php` includes fork behavior:

- `getRow()` returns the full row model.
- `beforeRender(Closure $callback)` can hide an action per row.
- `shouldRender()` is called by action displayers.
- Subclasses may set protected `$cssClass`.
- Rendered links include `title`.
- `retrieveModel()` uses `withTrashed()` when the model uses soft deletes.

Do not add filtering only in Blade. Use `shouldRender()` so action lists, dropdowns, and empty action columns stay consistent.

## Action Displayers

`src/Grid/Displayers/Actions/Actions.php` is class-keyed.

- `add(RowAction $action)` stores custom actions by class name.
- Default actions are stored by class name.
- `getAction($className)` can retrieve default or custom actions after preparation.
- `filterActions()` removes disabled or hidden actions.
- `prependDefaultActions()` is called before the user callback.
- `enableRestore()` adds the restore action class.

This differs from list-only behavior in older OpenAdmin. Preserve class keys when adding features that need to mutate or find an action.

## Restore Support

Soft delete restore is a fork feature.

- Row restore action: `src/Grid/Actions/Restore.php`.
- Batch restore action: `src/Grid/Tools/BatchRestore.php`.
- Batch enable flag: `Grid\Tools\BatchActions::$enableRestore`.
- `BatchActions::enableRestore()` toggles the default batch restore action.
- Restore row action only appears for rows where `trashed()` exists and returns true.
- Batch and row retrieve flows use `withTrashed()` through action model retrieval.

When adding restore UI, ensure the grid query includes trashed models where appropriate. The action cannot show for a trashed row that is not in the collection.

## Action Responses

`src/Actions/Response.php` supports normal response continuations:

- `refresh()`
- `download($url)`
- `redirect($url)`
- `location($location)`
- `open($url)`
- `html($html)`

Fork addition:

- `callFunction($functionName, array $args = [])`

`open-admin-actions.js` resolves `then.action == 'call'` with `eval()` after checking the function exists. Only use this for trusted internal functions and keep function names stable.

## Modal Action Forms

`src/Actions/Interactor/Form.php` has important fork changes:

- `addField(Field $field)` is public.
- Fields are pushed into the modal form via `getForm()->pushField($field)`.
- Modal form model can be the row model when the action has `row()`.
- Field IDs are suffixed with row key in row-action modals to avoid duplicate IDs.
- `actionFuncName()` creates a global JS function for opening the modal.
- If the action has an `onclick` attribute, automatic event binding is skipped so callers can manually call the generated function.

When adding a modal row action, test multiple rows on the same page to catch duplicate ID and duplicate submit-handler issues.

## Batch Actions

Batch actions get selected row IDs from `admin.grid.selected`.

- If `_key` is a string, `BatchAction::retrieveModel()` splits it by comma.
- Soft-deleted models use `withTrashed()`.
- Batch actions render as dropdown items with the short class name.
- Built-in batch actions are assembled by `Grid\Tools\BatchActions`.

Keep batch actions compatible with both selected IDs and model collections.

## JavaScript Coupling

Action JS lives in `resources/assets/open-admin/js/open-admin-actions.js`.

- `admin.actions.add()` binds configured actions.
- `admin.actions.actionResolver()` handles HTML, SweetAlert, toastr, and `then`.
- `admin.actions.actionCatcher()` displays server JSON errors.
- Response continuations must be added in both PHP `Response` and JS resolver.

If changing action response shape, update both PHP and JS.
