---
name: open-admin-forms
description: Form and field internals for this OpenAdmin fork. Use when creating, reviewing, or changing Form, Form Builder, Form Field classes, nested forms, hasMany, validation, relation saves, field options, resettable defaults, cascades, modal action forms, form Blade views, or form JavaScript behavior.
---

# OpenAdmin Forms

## Key Files

- Form orchestrator: `src/Form.php`.
- Builder: `src/Form/Builder.php`.
- Base field: `src/Form/Field.php`.
- Field registry: `src/Form/Concerns/HasFields.php`.
- Form attributes: `src/Form/Concerns/HasFormAttributes.php`.
- Nested forms: `src/Form/NestedForm.php`.
- Field classes: `src/Form/Field`.
- Form views: `resources/views/form`.
- Form runtime JS: `resources/assets/open-admin/js/open-admin-form.js`.
- Resettable runtime JS: `resources/assets/open-admin/js/open-admin-resettable.js`.

## Save Pipeline

Preserve the forked save flow.

- Public `store()` catches `FormValidationException`, `FormPrepareException`, and `FormSavedException`, then returns AJAX or redirect success responses.
- Internal `saveNew()` performs validation, `prepare()`, transaction, insert, relation update, and saved hook. It throws the form exceptions to carry response objects.
- Public `update($id, $data = null)` catches the same exception types, handles editable column responses, then returns AJAX or redirect success responses.
- Internal `saveExisting()` loads the model, sets field originals, validates, prepares, updates, updates relations, and calls saved hooks.
- Do not revert this back to the original OpenAdmin pattern where every internal step returns responses directly.

Exception classes:

- `src/Exception/FormValidationException.php`
- `src/Exception/FormPrepareException.php`
- `src/Exception/FormSavedException.php`

## Relation Saving

`Form::updateRelation($relationsData, Model $curentModel, $needPrepare = true)` is fork-specific.

- It accepts the current model explicitly so nested relation updates can recurse.
- It accounts for relation fields that must still be prepared when input is absent, especially fields with `public $must_prepare = true`.
- It handles `BelongsToMany`, `MorphToMany`, `HasOne`, `MorphOne`, `BelongsTo`, `MorphTo`, `HasMany`, and `MorphMany`.
- For `BelongsTo` and `MorphTo`, it can detect inner relations in prepared data, save the parent, then recursively update inner relations with `$needPrepare = false`.
- For `HasMany` and `MorphMany`, `_remove_` deletes child rows; otherwise children are `fill()`ed and saved.

When changing relation fields, inspect:

- `Form::getRelations()`
- `Form::prepareUpdate()`
- `Form::prepareInsert()`
- `Form::pushField()`
- `src/Form/Field/MultipleFile.php`

## Base Field Behavior

Important fork additions in `src/Form/Field.php`:

- `setId()` and `setValue()` exist and are used by modal/nested scenarios.
- `defaultOnNull()` is separate from `defaultOnEmpty()`.
- `prepare()` applies `defaultOnNull` before `defaultOnEmpty`.
- `resettable()` is only active when `defaultOnNull()` is not null.
- `variables()` emits `defaultValue`, `defaultOnNull`, `isResettable`, and `resettableName`.
- `setWidth($field, $label, $reset)` includes reset column width.
- `help($text, $mode, $icon)` supports `text`, `tooltip`, and `popover`.
- Placeholder fallback is the label, not `trans('admin.input').' '.$label`.
- Element classes are normalized for array names and get `is-invalid` when session errors match.
- `__toString()` returns rendered content directly.

## Field Registry

New or forked fields registered in `HasFields` include:

- `selectList` to `Field\SelectList`.
- `columns` to `Field\Columns`.
- `radioList` to `Field\RadioList`.
- Existing `keyValue`, `list`, `belongsTo`, and `belongsToMany` have fork-specific behavior.

`Admin::makeField($type, $fieldName, ...$args)` creates a field from the registry and is used by external code and action forms.

## Options Sources

Several fields accept non-array option sources.

- `src/Form/Field/Interfaces/OptionSourceInterface.php` defines `toOptionArray(): array`.
- Base `Field::options()` accepts an `Arrayable`, a class implementing `OptionSourceInterface`, or a JSON string.
- `Select::options()` accepts a model class for reload-selected behavior, an `OptionSourceInterface` class, a JSON string, a URL for remote loading, arrays, collections, and closures.
- `Checkbox::options()` also recognizes `OptionSourceInterface`.

When adding option-capable fields, keep this interface support consistent.

## Select Family

`src/Form/Field/Select.php` is heavily changed.

- `hideEmpty()` removes the empty option.
- `useNative()` disables ChoicesJS.
- `useChoicesjs()` re-enables ChoicesJS.
- `load()` and `ajax()` build ChoicesJS scripts and use `admin.ajax.post`.
- Remote options pass current selected value when available.
- ChoicesJS instances are stored in `window.choices_vars`.
- Translated ChoicesJS labels come from `admin.choices.*`.
- `SelectList` renders the normal select view as a native list, adds class `select-as-list`, sets `size`, and disables empty option by default.
- `Listbox` registers `window.listbox_vars` for resettable support.

## Custom Fields

- `Columns` collects fields added inside its `add($width, Closure $content)` callback, renders them in Bootstrap columns, then hides the collected fields from normal rendering.
- `KeyValue` supports custom key/value labels through constructor arguments and `useRandomSelector()` for unique DOM selectors.
- `ListField` supports Enter to focus/add rows, Delete to remove empty rows, and multiline paste to create rows.
- `SwitchField` uses values `1` and `0` by default, supports `values($on, $off)`, and uses cascade behavior.
- `Number` uses `inputmode="numeric"` and the vendored `fields/number-input.js`.
- `RadioList` extends `Radio` and renders via `resources/views/form/radiolist.blade.php`.

## Resettable Fields

Resettable fields use a two-part input model.

- A field becomes resettable only when `resettable()` is true and `defaultOnNull()` is set.
- Field names gain `[value]` for the actual field value.
- The reset checkbox uses `[inherit]` via `resettableName`.
- Blade footer renders checkbox `.reset-field-to-default`.
- `ResettableField` supports ChoicesJS, Listbox, native select, checkbox, radio, switch, textarea, and regular input.

When adding a new field type that can be resettable:

- Ensure it has stable element class and selector behavior.
- Register any JS widget instance in a predictable `window.*_vars` collection.
- Update `open-admin-resettable.js` if the widget cannot be handled as a native input.

## Cascades

Cascade logic lives in `src/Form/Field/Traits/CanCascadeFields.php`.

- Operators include `=`, `>`, `<`, `>=`, `<=`, `!=`, `in`, `notIn`, `has`, `oneIn`, and `oneNotIn`.
- Field values are string-normalized for comparison.
- Cascade group class names derive from the field element class.
- Supported front-end source field types are switch, radio variants, select variants, belongsTo variants, multiple select, and checkbox variants.

When fixing cascade selectors, verify both PHP class generation and JavaScript selectors.

## Nested And HasMany

`HasMany` has fork additions:

- Dot-notated inner relations are supported for building related forms and key names.
- Enter key is blocked inside hasMany inputs to prevent accidental submit.
- `hideDeleteText()` and `hideAddText()` hide button text.
- Default, tab, and table modes share much of the same add/remove script.
- Removed rows are hidden and marked with `_remove_ = 1`.

`NestedForm` has resettable support and formats resettable relation names with `[value]`.

## Blade Coupling

Many field changes require matching Blade variables.

- `_footer.blade.php` renders errors, help, inline close markup, section separator, and resettable checkbox.
- `help-block.blade.php` handles text, tooltip, and popover modes.
- `select.blade.php` uses `emptyOption`.
- `columns.blade.php` calls each collected field render manually.
- Keep `resources/views/actions/form` in mind for modal action forms; these are not always the same as normal form views.

## Safe Change Checklist

- Inspect field class, normal form view, action form view, and JS runtime together.
- Preserve public `store()` and `update()` response behavior.
- Preserve `must_prepare` behavior for upload/relation fields.
- Test both normal page forms and modal action forms when a field can appear in both.
- Check old input, validation errors, AJAX submit, PJAX submit, and resettable mode.
