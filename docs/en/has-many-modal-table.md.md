[Back to README](../README.md)

# HasMany Modal Table

`HasMany::useModalTable()` renders a `hasMany` relation as a compact table with row-level actions. The row fields are edited inside a Bootstrap modal instead of being rendered directly in every table cell.

Use this mode when a nested relation has enough fields that the regular inline table becomes too wide or hard to scan.

## Basic Usage

```php
use OpenAdmin\Admin\Form;

$form->hasMany('items', 'Items', function (Form\NestedForm $form) {
    $form->text('group');
    $form->text('title')->rules('required');
    $form->number('sort_order');
})->useModalTable(['group', 'title']);
```

The optional array passed to `useModalTable()` controls the preview columns shown in the table. The full nested form still lives in the modal.

## Preview Columns

Pass a list of field names to use the nested field labels:

```php
$form->hasMany('items', function (Form\NestedForm $form) {
    $form->text('group', 'Group');
    $form->text('title', 'Title');
})->useModalTable(['group', 'title']);
```

Pass an associative array to override preview labels:

```php
$form->hasMany('items', function (Form\NestedForm $form) {
    $form->text('group');
    $form->text('title');
})->useModalTable([
    'group' => 'Section',
    'title' => 'Display title',
]);
```

If no columns are provided, OpenAdmin uses all non-hidden nested fields as preview columns.

## Row Editing Flow

Each row has an `Edit` action. Clicking it opens the modal for that row.

Inside the modal:

| Action | Behavior |
| --- | --- |
| `Apply` | Keeps the edited values, refreshes the row preview, and closes the modal. |
| `Cancel` or close | Restores the values captured before the modal was opened. |

Validation errors do not automatically open modals. Rows containing invalid nested fields are highlighted in the table, so the user can choose which row to edit.

## Delete and Restore Flow

For persisted rows, delete is a two-step action:

1. The first click marks the row as deleted, grays it out, hides `Edit`, and shows `Restore`.
2. `Restore` clears the delete mark and makes the row editable again.
3. Clicking delete again while the row is marked removes it from the visible table state.

For newly added rows, delete removes the unsaved row immediately.

## Sorting

`useModalTable()` supports the same persisted table sorting flow as `useTable()`:

```php
$form->hasMany('items', function (Form\NestedForm $form) {
    $form->text('group');
    $form->text('title');
})
    ->sortable()
    ->sortColumn('sort_order')
    ->sortGroupColumn('group')
    ->sortWith('buttons');
```

Available sort UI modes:

| Mode | Behavior |
| --- | --- |
| `drag` | Drag handle only. |
| `buttons` | Up/down buttons only. |
| `all` | Drag handle and up/down buttons. |

When `sortGroupColumn()` is set, order values are normalized inside each group.

## Button Customization

The modal table uses `HasAddDeleteButtons` options for add, edit, delete, and restore-style controls.

```php
$items = $form->hasMany('items', function (Form\NestedForm $form) {
    $form->text('title');
})->useModalTable(['title']);

$items->hideAddText();
$items->hideEditText();
$items->hideDeleteIcon();

$items->addButtonText('Add item');
$items->editButtonIcon('icon-pencil');
$items->deleteButtonText('Remove');
```

Edit-specific methods:

| Method | Purpose |
| --- | --- |
| `editButtonText(string $text)` | Sets custom edit button text. |
| `editButtonIcon(?string $icon)` | Sets or clears the edit icon. |
| `hideEditText()` | Hides edit button text. |
| `hideEditIcon()` | Hides edit button icon. |

`Restore` uses the delete button visibility options so that delete/restore actions stay visually consistent.

## Notes

- Modal table markup intentionally does not use the `table-with-fields` CSS class. That class is for inline field tables and makes modal-table layout too constrained.
- Nested fields keep their normal form rendering inside the modal, but modal-table CSS gives labels and inputs proportions that fit modal width.
- Preview values are escaped in the Blade view.
- Hidden fields, IDs, remove flags, and sort columns remain part of the nested form payload.

## See Also

- [README](../README.md) - package overview and installation.
- [Contributing](../CONTRIBUTING.md) - contribution workflow.
- [HasMany source](../src/Form/Field/HasMany.php) - implementation details.
