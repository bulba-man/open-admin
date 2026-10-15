[Back to README](../README.md)

# HasMany Modal Table

`HasMany::useModalTable()` отображает `hasMany` relation как компактную таблицу с actions для каждой строки. Поля строки редактируются внутри Bootstrap modal, а не рендерятся прямо в ячейках таблицы.

Этот режим полезен, когда у nested relation достаточно полей, чтобы обычная inline table становилась слишком широкой или неудобной для просмотра.

## Basic Usage

```php
use OpenAdmin\Admin\Form;

$form->hasMany('items', 'Items', function (Form\NestedForm $form) {
    $form->text('group');
    $form->text('title')->rules('required');
    $form->number('sort_order');
})->useModalTable(['group', 'title']);
```

Необязательный массив в `useModalTable()` задает preview columns, которые будут показаны в таблице. Полная nested form остается внутри modal.

## Preview Columns

Передайте список field names, чтобы использовать labels из nested fields:

```php
$form->hasMany('items', function (Form\NestedForm $form) {
    $form->text('group', 'Group');
    $form->text('title', 'Title');
})->useModalTable(['group', 'title']);
```

Передайте associative array, чтобы переопределить preview labels:

```php
$form->hasMany('items', function (Form\NestedForm $form) {
    $form->text('group');
    $form->text('title');
})->useModalTable([
    'group' => 'Section',
    'title' => 'Display title',
]);
```

Если columns не переданы, OpenAdmin использует все non-hidden nested fields как preview columns.

## Row Editing Flow

У каждой строки есть action `Edit`. При клике открывается modal этой строки.

Внутри modal:

| Action | Behavior |
| --- | --- |
| `Apply` | Сохраняет отредактированные значения в текущем DOM-состоянии, обновляет preview строки и закрывает modal. |
| `Cancel` or close | Восстанавливает значения, которые были зафиксированы перед открытием modal. |

Validation errors не открывают modal автоматически. Строки, внутри которых есть invalid nested fields, подсвечиваются в таблице, чтобы пользователь сам выбрал нужную строку для редактирования.

## Delete and Restore Flow

Для persisted rows удаление работает в два шага:

1. Первый клик помечает строку как deleted, делает ее серой, скрывает `Edit` и показывает `Restore`.
2. `Restore` снимает delete mark и снова делает строку редактируемой.
3. Повторный delete по уже помеченной строке удаляет ее из видимого состояния таблицы.

Для новых, еще не сохраненных строк delete сразу убирает строку.

## Sorting

`useModalTable()` поддерживает тот же persisted table sorting flow, что и `useTable()`:

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

Доступные sort UI modes:

| Mode | Behavior |
| --- | --- |
| `drag` | Только drag handle. |
| `buttons` | Только кнопки up/down. |
| `all` | Drag handle и кнопки up/down. |

Если задан `sortGroupColumn()`, значения order нормализуются внутри каждой group.

### Явный порядок групп

В `useTable()` и `useModalTable()` можно задать порядок групп независимо от позиций строк:

```php
$items
    ->sortable()
    ->sortColumn('position')
    ->sortGroupColumn('group_id')
    ->sortGroupOrder([20, 10, 30]);
```

Поле `group_id` должно входить во вложенную форму. Группы отображаются как `20 → 10 → 30`, а строки внутри каждой группы — по `position`. Неуказанные непустые группы идут далее по прежнему правилу сравнения ID, строки без группы (`null`, отсутствующее значение или `''`) — последними. `0` и `'0'` обозначают полноценную группу; integer ID и его строковое представление из HTML совпадают. Строковые ID, включая ведущие нули, сохраняются; повторные ID в списке учитываются один раз, отсутствующие группы не создают строк.

Без `sortGroupOrder()` поведение остаётся прежним. `sortGroupOrder([])` включает новый порядок с пустой группой в конце, без приоритетных групп. Настройка действует только при включённой сортировке и заданном `sortGroupColumn()`.

Для получения списка из заполненной родительской формы передайте Closure:

```php
$items->sortGroupOrder(function (Form $form): array {
    return $form->model()->getAttribute('group_order') ?? [];
});
```

В этом примере `group_order` — атрибут модели приложения с массивом ID. Callback получает родительскую `Form` и вызывается один раз при каждом отображении поля, после заполнения формы. При validation и сохранении он не вызывается. Результат используется и для серверных строк, и для клиентского порядка.

После ошибки валидации сохраняются ключи, пользовательские значения, новые и удалённые строки, а также отправленная последовательность строк внутри группы. Позиции при сохранении независимо нумеруются от нуля в каждой группе; удалённые строки не участвуют. Drag и кнопки не переносят строки между группами. Изменение поля группы перемещает строку в соответствующий блок; отмена modal-редактирования возвращает значения и положение строки.

Уже известные группы используют подготовленный сервером порядок. Если на клиенте впервые введён неизвестный ID, группа вставляется перед первой неизвестной группой с большим ID по прежнему сравнению, иначе — в конец неизвестного блока, перед пустой группой. При смешанных числовых и текстовых ID это не повторная полная сортировка: взаимный порядок известных групп сохраняется до нового отображения формы. Автоматической синхронизации с другой таблицей групп нет; список предоставляет приложение.

## Button Customization

Modal table использует options из `HasAddDeleteButtons` для add, edit, delete и restore-style controls.

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
| `editButtonText(string $text)` | Задает текст edit button. |
| `editButtonIcon(?string $icon)` | Задает или очищает edit icon. |
| `hideEditText()` | Скрывает текст edit button. |
| `hideEditIcon()` | Скрывает edit icon. |

`Restore` использует delete button visibility options, чтобы actions удаления и восстановления выглядели согласованно.

## Notes

- Modal table markup намеренно не использует CSS class `table-with-fields`. Этот class нужен для inline field tables и делает layout modal-table слишком тесным.
- Nested fields сохраняют обычный form rendering внутри modal, но CSS modal-table задает proportions labels и inputs под ширину modal.
- Preview values экранируются в Blade view.
- Hidden fields, IDs, remove flags и sort columns остаются частью nested form payload.

## See Also

- [README](../README.md) - package overview and installation.
- [Contributing](../CONTRIBUTING.md) - contribution workflow.
- [HasMany source](../src/Form/Field/HasMany.php) - implementation details.
