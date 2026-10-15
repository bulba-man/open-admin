<?php

namespace OpenAdmin\Admin\Form\Field;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Relations\HasMany as Relation;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;
use OpenAdmin\Admin\Admin;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Form\Field;
use OpenAdmin\Admin\Form\Field\Traits\HasAddDeleteButtons;
use OpenAdmin\Admin\Form\Field\Traits\Sortable;
use OpenAdmin\Admin\Form\NestedForm;
use OpenAdmin\Admin\Widgets\Form as WidgetForm;

/**
 * Class HasMany.
 */
class HasMany extends Field
{
    use HasAddDeleteButtons;
    use Sortable;

    protected const MODAL_TABLE_PRESENT_KEY = '__modal_table_present__';

    /**
     * Relation name.
     *
     * @var string
     */
    protected $relationName = '';

    /**
     * Form builder.
     *
     * @var \Closure
     */
    protected $builder = null;

    /**
     * Form data.
     *
     * @var array
     */
    protected $value = [];

    /**
     * View Mode.
     *
     * Supports `default` and `tab` currently.
     *
     * @var string
     */
    protected $viewMode = 'default';

    /**
     * verticalAlign.
     *
     * Supports `middle`, `top` and `bottom`
     *
     * @var string
     */
    protected $verticalAlign = 'middle';

    /**
     * Available views for HasMany field.
     *
     * @var array
     */
    protected $views = [
        'default' => 'admin::form.hasmany',
        'tab' => 'admin::form.hasmanytab',
        'table' => 'admin::form.hasmanytable',
        'modalTable' => 'admin::form.hasmanymodaltable',
    ];

    /**
     * Options for template.
     *
     * @var array
     */
    protected $options = [
        'allowCreate' => true,
        'allowDelete' => true,
        'sortable' => false,
        'sortColumn' => 'order',
        'sortGroupColumn' => null,
        'sortWith' => 'drag',
        'modalTableColumns' => [],
        'deleteButShowText' => true,
        'deleteButShowIcon' => true,
        'editButShowText' => true,
        'editButShowIcon' => true,
        'addButShowText' => true,
        'addButShowIcon' => true,
    ];

    /**
     * Distinct fields.
     *
     * @var array
     */
    protected $distinctFields = [];

    /**
     * Column used to persist table row order.
     *
     * @var string
     */
    protected $sortColumn = 'order';

    /**
     * Optional column used to restrict sorting inside groups.
     *
     * @var string|null
     */
    protected $sortGroupColumn = null;

    /** @var array<int, int|string|null>|\Closure|null */
    protected $sortGroupOrder = null;

    /** @var list<string> */
    protected array $resolvedSortGroupOrder = [];

    /** @var list<string> */
    protected array $sortableGroups = [];

    /** @var array<int|string, bool> */
    protected array $sortableRemovedRows = [];

    /**
     * Sort UI mode for HasMany table rows.
     *
     * @var string
     */
    protected $sortWith = 'drag';

    /**
     * Supported HasMany table sort modes.
     *
     * @var array<int, string>
     */
    protected $sortWithOptions = ['drag', 'buttons', 'all'];

    /**
     * Create a new HasMany field instance.
     *
     * @param  array  $arguments
     */
    public function __construct($relationName, $arguments = [])
    {
        $this->relationName = $relationName;

        $this->column = $relationName;
        $this->id = $this->formatId($relationName);

        if (count($arguments) == 1) {
            $this->label = $this->formatLabel();
            $this->builder = $arguments[0];
        }

        if (count($arguments) == 2) {
            [$this->label, $this->builder] = $arguments;
        }
    }

    /**
     * Get validator for this field.
     *
     *
     * @return bool|Validator
     */
    public function getValidator(array $input)
    {
        if (! array_key_exists($this->column, $input)) {
            return false;
        }

        $input = Arr::only($input, $this->column);

        unset($input[$this->column][static::MODAL_TABLE_PRESENT_KEY]);

        /** unset item that contains remove flag */
        foreach ($input[$this->column] as $key => $value) {
            if (! is_array($value) || ! empty($value[NestedForm::REMOVE_FLAG_NAME])) {
                unset($input[$this->column][$key]);
            }
        }

        if (empty($input[$this->column])) {
            return false;
        }

        $form = $this->buildNestedForm($this->column, $this->builder);

        $rules = $attributes = [];

        /* @var Field $field */
        foreach ($form->fields() as $field) {
            if (! $fieldRules = $field->getRules()) {
                continue;
            }

            $column = $field->column();

            if (is_array($column)) {
                foreach ($column as $key => $name) {
                    $rules[$name.$key] = $fieldRules;
                }

                $this->resetInputKey($input, $column);
            } else {
                $rules[$column] = $fieldRules;
            }

            $attributes = array_merge(
                $attributes,
                $this->formatValidationAttribute($input, $field->label(), $column)
            );
        }

        Arr::forget($rules, NestedForm::REMOVE_FLAG_NAME);

        if (empty($rules)) {
            return false;
        }

        $newRules = [];
        $newInput = [];

        foreach ($rules as $column => $rule) {
            foreach (array_keys($input[$this->column]) as $key) {
                $newRules["{$this->column}.$key.$column"] = $rule;
                if (isset($input[$this->column][$key][$column]) &&
                    is_array($input[$this->column][$key][$column])) {
                    foreach ($input[$this->column][$key][$column] as $vkey => $value) {
                        $newInput["{$this->column}.$key.{$column}$vkey"] = $value;
                    }
                }
            }
        }

        if (empty($newInput)) {
            $newInput = $input;
        }

        $this->appendDistinctRules($newRules);

        return \validator($newInput, $newRules, $this->getValidationMessages(), $attributes);
    }

    /**
     * Set distinct fields.
     *
     *
     * @return $this
     */
    public function distinctFields(array $fields)
    {
        $this->distinctFields = $fields;

        return $this;
    }

    /**
     * Set sortable.
     *
     * @return $this
     */
    public function sortable($set = true)
    {
        $this->options['sortable'] = $set;

        return $this;
    }

    /**
     * Set the column used to persist HasMany table order.
     *
     * @return $this
     */
    public function sortColumn(string $column = 'order')
    {
        $this->sortColumn = $column;
        $this->options['sortColumn'] = $column;

        return $this;
    }

    /**
     * Set the optional column used to restrict sorting inside groups.
     *
     * @return $this
     */
    public function sortGroupColumn(?string $column = null)
    {
        $this->sortGroupColumn = $column;
        $this->options['sortGroupColumn'] = $column;

        return $this;
    }

    /**
     * Set group priority, resolved with the parent Form when rendering.
     *
     * @param  array<int, int|string|null>|\Closure(Form): array<int, int|string|null>  $order
     */
    public function sortGroupOrder(array|\Closure $order): self
    {
        $this->sortGroupOrder = $order;

        return $this;
    }

    protected function hasSortGroupOrder(): bool
    {
        return $this->isSortableTable()
            && $this->sortGroupColumn !== null
            && $this->sortGroupColumn !== ''
            && $this->sortGroupOrder !== null;
    }

    protected function resolveSortGroupOrder(): void
    {
        $this->resolvedSortGroupOrder = [];
        $this->sortableGroups = [];
        $this->sortableRemovedRows = [];

        if (! $this->hasSortGroupOrder()) {
            return;
        }

        $order = $this->sortGroupOrder instanceof \Closure
            ? ($this->sortGroupOrder)($this->form)
            : $this->sortGroupOrder;

        if (! is_array($order)) {
            throw new \InvalidArgumentException('HasMany sortGroupOrder must return an array of group IDs.');
        }

        foreach ($order as $group) {
            if ($group === null || $group === '') {
                continue;
            }

            if (! is_int($group) && ! is_string($group)) {
                throw new \InvalidArgumentException('HasMany sortGroupOrder IDs must be integers or strings.');
            }

            $group = (string) $group;

            if (! in_array($group, $this->resolvedSortGroupOrder, true)) {
                $this->resolvedSortGroupOrder[] = $group;
            }
        }

        $this->sortableGroups = [...$this->resolvedSortGroupOrder, ''];
    }

    /**
     * Set the sort UI mode for HasMany table rows.
     *
     * @return $this
     */
    public function sortWith(string $mode)
    {
        if (! in_array($mode, $this->sortWithOptions, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid HasMany sort mode [%s]. Supported modes are: %s.',
                $mode,
                implode(', ', $this->sortWithOptions)
            ));
        }

        $this->sortWith = $mode;
        $this->options['sortWith'] = $mode;

        return $this;
    }

    /**
     * Append distinct rules.
     */
    protected function appendDistinctRules(array &$rules)
    {
        foreach ($this->distinctFields as $field) {
            $rules["{$this->column}.*.$field"] = 'distinct';
        }
    }

    /**
     * Format validation attributes.
     *
     * @param  array  $input
     * @param  string  $label
     * @param  string  $column
     * @return array
     */
    protected function formatValidationAttribute($input, $label, $column)
    {
        $new = $attributes = [];

        if (is_array($column)) {
            foreach ($column as $index => $col) {
                $new[$col.$index] = $col;
            }
        }

        foreach (array_keys(Arr::dot($input)) as $key) {
            if (is_string($column)) {
                if (Str::endsWith($key, ".$column")) {
                    $attributes[$key] = $label;
                }
            } else {
                foreach ($new as $k => $val) {
                    if (Str::endsWith($key, ".$k")) {
                        $attributes[$key] = $label."[$val]";
                    }
                }
            }
        }

        return $attributes;
    }

    /**
     * Reset input key for validation.
     *
     * @param  array  $column  $column is the column name array set
     * @return void.
     */
    protected function resetInputKey(array &$input, array $column)
    {
        /**
         * flip the column name array set.
         *
         * for example, for the DateRange, the column like as below
         *
         * ["start" => "created_at", "end" => "updated_at"]
         *
         * to:
         *
         * [ "created_at" => "start", "updated_at" => "end" ]
         */
        $column = array_flip($column);

        /**
         * $this->column is the inputs array's node name, default is the relation name.
         *
         * So... $input[$this->column] is the data of this column's inputs data
         *
         * in the HasMany relation, has many data/field set, $set is field set in the below
         */
        foreach ($input[$this->column] as $index => $set) {
            /*
             * foreach the field set to find the corresponding $column
             */
            foreach ($set as $name => $value) {
                /*
                 * if doesn't have column name, continue to the next loop
                 */
                if (! array_key_exists($name, $column)) {
                    continue;
                }

                /**
                 * example:  $newKey = created_atstart.
                 *
                 * Σ( ° △ °|||)︴
                 *
                 * I don't know why a form need range input? Only can imagine is for range search....
                 */
                $newKey = $name.$column[$name];

                /*
                 * set new key
                 */
                Arr::set($input, "{$this->column}.$index.$newKey", $value);
                /*
                 * forget the old key and value
                 */
                Arr::forget($input, "{$this->column}.$index.$name");
            }
        }
    }

    /**
     * Prepare input data for insert or update.
     *
     * @param  array|false  $input
     * @return array|false
     */
    public function prepare($input)
    {
        if ($input === false) {
            return false;
        }

        if (! is_array($input)) {
            return [];
        }

        if (array_key_exists(static::MODAL_TABLE_PRESENT_KEY, $input)) {
            unset($input[static::MODAL_TABLE_PRESENT_KEY]);
        }

        if ($this->isSortableTable()) {
            $input = $this->normalizeSortableTableInput((array) $input);
        }

        $form = $this->buildNestedForm($this->column, $this->builder);

        return $form->setOriginal($this->original, $this->getKeyName())->prepare($input);
    }

    /**
     * Build a Nested form.
     *
     * @param  string  $column
     * @param  null  $model
     * @return NestedForm
     */
    protected function buildNestedForm($column, \Closure $builder, $model = null)
    {
        return $this->configureNestedForm(new NestedForm($column, $model), $builder);
    }

    protected function configureNestedForm(NestedForm $form, \Closure $builder): NestedForm
    {
        if ($this->form instanceof WidgetForm) {
            $form->setWidgetForm($this->form);
        } else {
            $form->setForm($this->form);
        }

        call_user_func($builder, $form);

        if ($this->isSortableTable() && ! $this->nestedFormHasColumn($form, $this->sortColumn)) {
            $form->hidden($this->sortColumn);
        }

        $form->hidden($this->getKeyName());

        $form->hidden(NestedForm::REMOVE_FLAG_NAME)->default(0)->addElementClass(NestedForm::REMOVE_FLAG_CLASS);

        return $form;
    }

    /**
     * Get the HasMany relation key name.
     *
     * @return string
     */
    protected function getKeyName()
    {
        if (is_null($this->form)) {
            return;
        }

        if (Str::contains($this->relationName, '.')) {
            $relations = Str::of($this->relationName)->explode('.')->toArray();
            $model = $this->form->model();
            foreach ($relations as $relationName) {
                $model = $model->{$relationName}()->getRelated();
            }

            return $model->getKeyName();
        }

        return $this->form->model()->{$this->relationName}()->getRelated()->getKeyName();
    }

    /**
     * Set view mode.
     *
     * @param  string  $mode  currently support `tab` mode.
     * @return $this
     */
    public function mode($mode)
    {
        $this->viewMode = $mode;

        return $this;
    }

    /**
     * Set view mode.
     *
     * @param  string  $mode  currently support `tab` mode.
     * @return $this
     */
    public function verticalAlign($align)
    {
        $this->verticalAlign = $align;

        return $this;
    }

    /**
     * Use tab mode to showing hasmany field.
     *
     * @return HasMany
     */
    public function useTab()
    {
        return $this->mode('tab');
    }

    /**
     * Use table mode to showing hasmany field.
     *
     * @return HasMany
     */
    public function useTable()
    {
        return $this->mode('table');
    }

    /**
     * Use modal table mode to showing hasmany field.
     *
     * @return HasMany
     */
    public function useModalTable(array $columns = [])
    {
        $this->options['modalTableColumns'] = $columns;

        return $this->mode('modalTable');
    }

    /**
     * Determine whether the persisted HasMany table sorting feature is active.
     */
    protected function isSortableTable(): bool
    {
        return in_array($this->viewMode, ['table', 'modalTable'], true)
            && ! empty($this->options['sortable'])
            && ! ($this instanceof Table);
    }

    /**
     * Normalize columns used by the modal table preview.
     */
    protected function modalTablePreviewColumns(NestedForm $form): array
    {
        $available = [];

        foreach ($form->fields() as $field) {
            if ($field instanceof Hidden) {
                continue;
            }

            foreach ((array) $field->column() as $column) {
                $available[$column] = [
                    'column' => $column,
                    'label' => $field->label(),
                ];
            }
        }

        $configured = $this->options['modalTableColumns'] ?? [];

        if (empty($configured)) {
            return array_values($available);
        }

        $columns = [];

        foreach ($configured as $column => $label) {
            if (is_int($column)) {
                $column = $label;
                $label = $available[$column]['label'] ?? Str::headline((string) $column);
            }

            $columns[] = [
                'column' => (string) $column,
                'label' => (string) $label,
            ];
        }

        return $columns;
    }

    /**
     * Build escaped preview values for related modal table rows.
     */
    protected function modalTablePreviews(array $forms, array $columns): array
    {
        $previews = [];

        foreach ($forms as $key => $form) {
            $attributes = $form->model() ? $form->model()->getAttributes() : [];

            foreach ($columns as $column) {
                $previews[$key][$column['column']] = Arr::get($attributes, $column['column'], '');
            }
        }

        return $previews;
    }

    /**
     * Check whether a nested form already contains the given column.
     */
    protected function nestedFormHasColumn(NestedForm $form, string $column): bool
    {
        foreach ($form->fields() as $field) {
            $fieldColumn = $field->column();

            if ($fieldColumn === $column) {
                return true;
            }

            if (is_array($fieldColumn) && in_array($column, $fieldColumn, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize submitted sort values by visible row order.
     */
    protected function normalizeSortableTableInput(array $input): array
    {
        $groupCounters = [];

        foreach ($input as &$record) {
            if (! is_array($record)) {
                continue;
            }

            if (Arr::get($record, NestedForm::REMOVE_FLAG_NAME) == 1) {
                continue;
            }

            $group = $this->sortableGroupValue($record);
            $groupCounters[$group] = $groupCounters[$group] ?? 0;
            Arr::set($record, $this->sortColumn, $groupCounters[$group]);
            $groupCounters[$group]++;
        }

        unset($record);

        return $input;
    }

    /**
     * Sort persisted/default values by configured group and order.
     */
    protected function sortSortableTableValues($values, bool $preserveRowOrder = false): array
    {
        /** @var list<array{key: int|string, data: mixed, group: string, order: int, position: int}> $rows */
        $rows = [];
        $position = 0;

        foreach ($values as $key => $data) {
            if ($data instanceof Arrayable) {
                $data = $data->toArray();
            }

            $row = is_array($data) ? $data : [];
            $order = Arr::get($row, $this->sortColumn);

            $rows[] = [
                'key' => $key,
                'data' => $data,
                'group' => $this->sortableGroupValue($row),
                'order' => $preserveRowOrder ? $position : (is_numeric($order) ? (int) $order : PHP_INT_MAX),
                'position' => $position,
            ];

            $position++;
        }

        $groupRanks = [];

        if ($this->hasSortGroupOrder()) {
            $unknownGroups = [];

            foreach ($rows as $row) {
                if ($row['group'] !== ''
                    && ! in_array($row['group'], $this->resolvedSortGroupOrder, true)
                    && ! in_array($row['group'], $unknownGroups, true)) {
                    $unknownGroups[] = $row['group'];
                }
            }

            usort($unknownGroups, fn (string $left, string $right): int => $left <=> $right);
            $this->sortableGroups = [...$this->resolvedSortGroupOrder, ...$unknownGroups, ''];
            $groupRanks = array_flip($this->sortableGroups);
        }

        usort($rows, function (array $left, array $right) use ($groupRanks): int {
            $leftGroup = $groupRanks ? $groupRanks[$left['group']] : $left['group'];
            $rightGroup = $groupRanks ? $groupRanks[$right['group']] : $right['group'];

            return [$leftGroup, $left['order'], $left['position']]
                <=> [$rightGroup, $right['order'], $right['position']];
        });

        $sorted = [];

        foreach ($rows as $row) {
            $sorted[$row['key']] = $row['data'];
        }

        return $sorted;
    }

    /**
     * Resolve a sortable group value from a row.
     */
    protected function sortableGroupValue(array $record): string
    {
        if ($this->sortGroupColumn === null) {
            return '';
        }

        $value = Arr::get($record, $this->sortGroupColumn, '');

        if ($value === null || $value === '') {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value) ?: '';
    }

    public function setTabLabelModelAttribute(string $attribute)
    {
        $this->options['attribute_for_tab_label'] = $attribute;

        return $this;
    }

    /**
     * Build Nested form for related data.
     *
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function buildRelatedForms()
    {
        if (is_null($this->form)) {
            return [];
        }

        $model = $this->form->model();

        if (Str::contains($this->relationName, '.')) {
            $relations = Str::of($this->relationName)->explode('.')->toArray();
            $lastRelationKey = array_key_last($relations);
            $lastRelationName = $relations[$lastRelationKey];
            unset($relations[$lastRelationKey]);

            foreach ($relations as $relationName) {
                $model = $model->{$relationName}()->getRelated();
            }

            $relation = call_user_func([$model, $lastRelationName]);
        } else {
            $relation = call_user_func([$model, $this->relationName]);
        }

        if (! $relation instanceof Relation && ! $relation instanceof MorphMany) {
            throw new \Exception('hasMany field must be a HasMany or MorphMany relation.');
        }

        $forms = [];

        /*
         * If redirect from `exception` or `validation error` page.
         *
         * Then get form data from session flash.
         *
         * Else get data from database.
         */
        $values = old($this->column);

        if ($this->hasSortGroupOrder() ? $values !== null : (bool) $values) {
            $values = (array) $values;
            unset($values[static::MODAL_TABLE_PRESENT_KEY]);

            if ($this->hasSortGroupOrder()) {
                $values = $this->sortSortableTableValues($values, true);
            }

            foreach ($values as $key => $data) {
                if (! $this->hasSortGroupOrder() && $data[NestedForm::REMOVE_FLAG_NAME] == 1) {
                    continue;
                }

                $model = $relation->getRelated()->replicate()->forceFill($data);

                if ($this->hasSortGroupOrder()) {
                    $nestedForm = $this->configureNestedForm(
                        (new NestedForm($this->column, $model))->setKey($key),
                        $this->builder
                    );
                    $this->sortableRemovedRows[$key] = Arr::get($data, NestedForm::REMOVE_FLAG_NAME) == 1;
                } else {
                    $nestedForm = $this->buildNestedForm($this->column, $this->builder, $model);
                }

                $forms[$key] = $nestedForm->fill($data);
            }
        } else {
            if (empty($this->value)) {
                $this->value = $this->getDefault();
            }

            if (empty($this->value)) {
                return [];
            }

            $values = $this->isSortableTable()
                ? $this->sortSortableTableValues($this->value)
                : $this->value;

            foreach ($values as $index => $data) {
                if ($data instanceof Arrayable) {
                    $data = $data->toArray();
                }

                $key = Arr::get($data, $relation->getRelated()->getKeyName(), $index);

                $model = $relation->getRelated()->replicate()->forceFill($data);

                $forms[$key] = $this->buildNestedForm($this->column, $this->builder, $model)
                    ->fill($data);
            }
        }

        return $forms;
    }

    /**
     * Setup script for this field in different view mode.
     *
     * @param  string  $script
     * @return void
     */
    protected function setupScript($script)
    {
        $method = 'setupScriptFor'.ucfirst($this->viewMode).'View';

        call_user_func([$this, $method], $script);
    }

    /**
     * Setup default template script.
     *
     * @param  string  $templateScript
     * @return void
     */
    protected function setupScriptForDefaultView($templateScript)
    {
        $removeClass = NestedForm::REMOVE_FLAG_CLASS;
        $defaultKey = NestedForm::DEFAULT_KEY_NAME;

        /**
         * When add a new sub form, replace all element key in new sub form.
         *
         * @example comments[new___key__][title]  => comments[new_{index}][title]
         *
         * {count} is increment number of current sub form count.
         */
        $script = <<<JS
document.querySelectorAll('#has-many-{$this->id}').forEach(hasMany => {
    var initializedAttribute = 'data-has-many-{$this->getJSSelector()}-initialized';

    if (hasMany.getAttribute(initializedAttribute) === '1') {
        return;
    }

    hasMany.setAttribute(initializedAttribute, '1');

    var index = 0;
    var add = Array.from(hasMany.querySelectorAll('.add')).find(add => add.closest('#has-many-{$this->id}') === hasMany);
    var orderedTable = hasMany.querySelector('table.has-many-{$this->id}[data-sort-group-order]');

    if (add) {
        add.addEventListener("click", function () {
            do {
                index++;
            } while (orderedTable && Array.from(orderedTable.tBodies[0].children).some(row => row.getAttribute('data-has-many-row-key') === 'new_' + index));

            var tpl = hasMany.querySelector('template.{$this->id}-tpl').innerHTML;
            tpl = tpl.replace(/{$defaultKey}/g, index);
            var clone = htmlToElement(tpl);
            addRemoveHasManyListener{$this->getJSSelector()}(clone.querySelector('.remove'), hasMany);

            clone.querySelectorAll('input').forEach(elem => {
                addEnterHasManyListener{$this->getJSSelector()}(elem);
            });

            hasMany.querySelector('.has-many-{$this->id}-forms').appendChild(clone);

            if (typeof(addHasManyTab{$this->getJSSelector()}) == 'function'){
                addHasManyTab{$this->getJSSelector()}(hasMany, index);
            }

            {$templateScript}
            admin.form.cascade(clone);
            admin.form.jsonFields();

            if (orderedTable) {
                hasMany.dispatchEvent(new CustomEvent('has-many:row-change'));
            }
            return false;

        });
    }

    hasMany.querySelectorAll('.remove').forEach(remove => {
        if (remove.closest('#has-many-{$this->id}') === hasMany) {
            addRemoveHasManyListener{$this->getJSSelector()}(remove, hasMany);
        }
    });

    hasMany.querySelectorAll('input').forEach(elem => {
        if (elem.closest('#has-many-{$this->id}') === hasMany) {
            addEnterHasManyListener{$this->getJSSelector()}(elem);
        }
    });
});

function addEnterHasManyListener{$this->getJSSelector()}(el){
    if (!el || el.getAttribute('data-has-many-enter-{$this->getJSSelector()}') === '1') {
        return;
    }

    el.setAttribute('data-has-many-enter-{$this->getJSSelector()}', '1');

    el.addEventListener("keydown", function (event) {
        if (event.keyCode == 13) {
            event.preventDefault();
            return false;
        }
    });
}

function addRemoveHasManyListener{$this->getJSSelector()}(remove, hasMany){
    if (!remove || remove.getAttribute('data-has-many-remove-{$this->getJSSelector()}') === '1') {
        return;
    }

    remove.setAttribute('data-has-many-remove-{$this->getJSSelector()}', '1');

    remove.addEventListener("click", function () {
        let form = this.closest('.has-many-{$this->id}-form');

        if (!form) {
            return false;
        }

        if (typeof(removeHasManyTab{$this->getJSSelector()}) == 'function'){
            removeHasManyTab{$this->getJSSelector()}(hasMany);
        }
        form.querySelectorAll('input').forEach(input => input.removeAttribute('required'));
        hide(form);

        let removeFlag = form.querySelector('.$removeClass');
        if (removeFlag) {
            removeFlag.value = 1;
        }

        if (hasMany.querySelector('table.has-many-{$this->id}[data-sort-group-order]')) {
            form.dispatchEvent(new CustomEvent('has-many:row-change', { bubbles: true }));
        }

        return false;
    });
}

JS;

        Admin::script($script);
    }

    /**
     * Setup tab template script.
     *
     * @param  string  $templateScript
     * @return void
     */
    protected function setupScriptForTabView($templateScript)
    {
        $removeClass = NestedForm::REMOVE_FLAG_CLASS;
        $defaultKey = NestedForm::DEFAULT_KEY_NAME;

        $this->setupScriptForDefaultView($templateScript);

        $script = <<<EOT
        function removeHasManyTab{$this->getJSSelector()}(hasMany){
            let active = hasMany.querySelector('.nav-link.active');
            if (active) {
                active.parentNode.remove();
            }

            let trigger = hasMany.querySelector('.nav-link:first-child');
            if (trigger){
                bootstrap.Tab.getOrCreateInstance(trigger).show();
            }
        }
        function addHasManyTab{$this->getJSSelector()}(hasMany, index){
            let tpl = hasMany.querySelector('template.{$this->id}-tab-tpl').innerHTML;
            tpl = tpl.replace(/{$defaultKey}/g, index);
            let clone = htmlToElement(tpl);
            let addTab = hasMany.querySelector('.add-tab')
            hasMany.querySelector('.nav').insertBefore(clone,addTab);
            bootstrap.Tab.getOrCreateInstance(clone.querySelector("a")).show();
        }

EOT;

        Admin::script($script);
    }

    /**
     * Setup default template script.
     *
     * @param  string  $templateScript
     * @return void
     */
    protected function setupScriptForTableView($templateScript)
    {
        $this->setupScriptForDefaultView($templateScript);

        if (! $this->isSortableTable()) {
            return;
        }

        $removeClass = NestedForm::REMOVE_FLAG_CLASS;
        $sortColumn = addslashes($this->sortColumn);
        $sortGroupColumn = addslashes($this->sortGroupColumn ?? '');
        $sortWith = addslashes($this->sortWith);
        $groupScript = $this->sortableGroupOrderScript();

        $script = <<<JS
document.querySelectorAll('#has-many-{$this->id}').forEach(hasMany => {
    var initializedAttribute = 'data-has-many-sortable-{$this->getJSSelector()}-initialized';

    if (hasMany.getAttribute(initializedAttribute) === '1') {
        return;
    }

    hasMany.setAttribute(initializedAttribute, '1');

    var tbody = hasMany.querySelector('.has-many-{$this->id}-forms');
    var sortColumn = '{$sortColumn}';
    var groupColumn = '{$sortGroupColumn}';
    var sortWith = '{$sortWith}';

    if (!tbody) {
        return;
    }

    {$groupScript}

    function columnSelector(column) {
        return '[name$="' + column.split('.').join('][').split('->').join('][').replace(/^/, '[').replace(/$/, ']') + '"]';
    }

    function rowIsRemoved(row) {
        var removeFlag = row.querySelector('.{$removeClass}');

        return removeFlag && removeFlag.value == '1';
    }

    function activeRows() {
        return Array.from(tbody.children).filter(row => {
            return row.style.display !== 'none' && !rowIsRemoved(row);
        });
    }

    function findField(row, column) {
        if (!column) {
            return null;
        }

        var selectors = ['[data-hasmany-sort-field="' + column + '"]', 'input' + columnSelector(column),
            'select' + columnSelector(column), 'textarea' + columnSelector(column)];

        for (var selector of selectors) {
            var field = Array.from(row.querySelectorAll(selector)).find(field => ownsGroupEvent({ target: field }));
            if (field) {
                return field;
            }
        }

        return null;
    }

    function groupValue(row) {
        if (!groupColumn) {
            return '';
        }

        var groupField = findField(row, groupColumn);

        return groupField ? groupField.value : '';
    }

    function refreshOrderValues() {
        refreshGroupOrder();
        var counters = Object.create(null);

        activeRows().forEach(row => {
            var group = groupValue(row);
            counters[group] = counters[group] || 0;

            var sortField = findField(row, sortColumn);
            if (sortField) {
                sortField.value = counters[group];
            }

            var number = row.querySelector('.has-many-sort-number');
            if (number) {
                number.textContent = counters[group] + 1;
            }

            counters[group]++;
        });
    }

    function moveRow(row, direction) {
        var rows = activeRows();
        var index = rows.indexOf(row);
        var target = rows[index + direction];

        if (!target || groupValue(target) !== groupValue(row)) {
            return;
        }

        if (direction < 0) {
            tbody.insertBefore(row, target);
        } else {
            tbody.insertBefore(target, row);
        }

        refreshOrderValues();
    }

    hasMany.addEventListener('click', event => {
        if (!ownsGroupEvent(event)) {
            return;
        }

        var button = event.target.closest('.has-many-sort-up, .has-many-sort-down');

        if (!button || !hasMany.contains(button)) {
            return;
        }

        event.preventDefault();

        var row = button.closest('.has-many-{$this->id}-form');

        if (!row) {
            return;
        }

        moveRow(row, button.classList.contains('has-many-sort-up') ? -1 : 1);
    });

    hasMany.addEventListener('change', event => {
        if (ownsGroupEvent(event) && groupColumn && event.target.matches(columnSelector(groupColumn))) {
            refreshOrderValues();
        }
    });

    hasMany.querySelectorAll('.add, .remove').forEach(button => {
        button.addEventListener('click', () => window.setTimeout(refreshOrderValues, 0));
    });

    hasMany.addEventListener('has-many:row-change', event => {
        if (ownsGroupEvent(event)) {
            refreshOrderValues();
        }
    });

    if ((sortWith === 'drag' || sortWith === 'all') && typeof Sortable !== 'undefined') {
        new Sortable(tbody, {
            animation: 150,
            handle: '.handle',
            filter: function (event, target) {
                var row = target.closest('.has-many-{$this->id}-form');
                return row && rowIsRemoved(row);
            },
            onStart: captureGroupDrag,
            onMove: function (event) {
                return !rowIsRemoved(event.dragged) && !rowIsRemoved(event.related)
                    && groupValue(event.dragged) === groupValue(event.related);
            },
            onEnd: function (event) {
                finishGroupDrag(event);
                refreshOrderValues();
            }
        });
    }

    refreshOrderValues();
});

JS;

        Admin::script($script);
    }

    /**
     * Shared group ordering for the two table initializers.
     */
    protected function sortableGroupOrderScript(): string
    {
        if (! $this->hasSortGroupOrder()) {
            return 'function refreshGroupOrder() {} function captureGroupDrag() {} function finishGroupDrag() {} function ownsGroupEvent() { return true; }';
        }

        return <<<'JS'
    var groupConfiguration = JSON.parse(tbody.closest('table').getAttribute('data-sort-group-order'));
    var orderedGroups = groupConfiguration.groups.slice();
    var dragRows = null;

    function ownsGroupEvent(event) {
        return event.target.closest('div[id^="has-many-"]:not(.has-many-modal)') === hasMany;
    }

    function compareGroupIds(left, right) {
        var numeric = /^\s*[+-]?(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?\s*$/;
        var integer = /^\s*[+-]?\d+\s*$/;

        if (numeric.test(left) && numeric.test(right)) {
            var leftNumber = integer.test(left) && integer.test(right) ? BigInt(left.trim()) : Number(left);
            var rightNumber = integer.test(left) && integer.test(right) ? BigInt(right.trim()) : Number(right);

            return leftNumber < rightNumber ? -1 : (leftNumber > rightNumber ? 1 : 0);
        }

        var encoder = new TextEncoder();
        var leftBytes = encoder.encode(left);
        var rightBytes = encoder.encode(right);

        for (var index = 0; index < Math.min(leftBytes.length, rightBytes.length); index++) {
            if (leftBytes[index] !== rightBytes[index]) {
                return leftBytes[index] < rightBytes[index] ? -1 : 1;
            }
        }

        return leftBytes.length - rightBytes.length;
    }

    function groupRank(group) {
        var rank = orderedGroups.indexOf(group);

        if (rank !== -1) {
            return rank;
        }

        rank = orderedGroups.length - 1;

        for (var index = groupConfiguration.priority.length; index < orderedGroups.length - 1; index++) {
            if (compareGroupIds(group, orderedGroups[index]) < 0) {
                rank = index;
                break;
            }
        }

        orderedGroups.splice(rank, 0, group);

        return rank;
    }

    function refreshGroupOrder() {
        var rows = Array.from(tbody.children);

        rows.forEach(row => {
            groupRank(groupValue(row));

            if (rowIsRemoved(row)) {
                row.querySelectorAll('[required]').forEach(field => {
                    field.setAttribute('data-has-many-modal-required', '1');
                    field.removeAttribute('required');
                });
            }
        });

        rows.sort((left, right) => groupRank(groupValue(left)) - groupRank(groupValue(right)));
        var cursor = tbody.firstElementChild;

        rows.forEach(row => {
            if (row === cursor) {
                cursor = cursor.nextElementSibling;
            } else {
                tbody.insertBefore(row, cursor);
            }
        });
    }

    function captureGroupDrag() {
        dragRows = Array.from(tbody.children);
    }

    function finishGroupDrag(event) {
        var rows = activeRows();
        var index = rows.indexOf(event.item);
        var rank = groupRank(groupValue(event.item));
        var previous = rows[index - 1];
        var next = rows[index + 1];

        if (dragRows && (rowIsRemoved(event.item)
            || (previous && groupRank(groupValue(previous)) > rank)
            || (next && groupRank(groupValue(next)) < rank))) {
            dragRows.forEach(row => tbody.appendChild(row));
        }

        dragRows = null;
    }
JS;
    }

    /**
     * Setup modal table template script.
     *
     * @param  string  $templateScript
     * @return void
     */
    protected function setupScriptForModalTableView($templateScript)
    {
        $removeClass = NestedForm::REMOVE_FLAG_CLASS;
        $defaultKey = NestedForm::DEFAULT_KEY_NAME;
        $sortColumn = addslashes($this->sortColumn);
        $sortGroupColumn = addslashes($this->sortGroupColumn ?? '');
        $sortWith = addslashes($this->sortWith);
        $sortable = $this->isSortableTable() ? 'true' : 'false';
        $groupScript = $this->sortableGroupOrderScript();

        $script = <<<JS
document.querySelectorAll('#has-many-{$this->id}').forEach(hasMany => {
    var initializedAttribute = 'data-has-many-modal-table-{$this->getJSSelector()}-initialized';

    if (hasMany.getAttribute(initializedAttribute) === '1') {
        return;
    }

    hasMany.setAttribute(initializedAttribute, '1');

    var index = 0;
    var tbody = hasMany.querySelector('.has-many-{$this->id}-forms');
    var add = Array.from(hasMany.querySelectorAll('.add')).find(add => add.closest('#has-many-{$this->id}') === hasMany);
    var sortColumn = '{$sortColumn}';
    var groupColumn = '{$sortGroupColumn}';
    var sortWith = '{$sortWith}';
    var sortable = {$sortable};
    var restoringGroupSnapshot = false;

    {$groupScript}

    function columnSelector(column) {
        return '[name$="' + column.split('.').join('][').split('->').join('][').replace(/^/, '[').replace(/$/, ']') + '"]';
    }

    function findField(row, column) {
        if (!column) {
            return null;
        }

        var selectors = ['[data-hasmany-sort-field="' + column + '"]', 'input' + columnSelector(column),
            'select' + columnSelector(column), 'textarea' + columnSelector(column)];

        for (var selector of selectors) {
            var field = Array.from(row.querySelectorAll(selector)).find(field => ownsGroupEvent({ target: field }));
            if (field) {
                return field;
            }
        }

        return null;
    }

    function fieldText(field) {
        if (!field) {
            return '';
        }

        if (field.tagName === 'SELECT') {
            return Array.from(field.selectedOptions).map(option => option.textContent.trim()).join(', ');
        }

        if (field.type === 'checkbox' || field.type === 'radio') {
            var fields = Array.from(field.closest('.modal-body').querySelectorAll('[name="' + field.name + '"]'));

            return fields.filter(input => input.checked).map(input => {
                var label = input.id ? input.closest('.modal-body').querySelector('label[for="' + input.id + '"]') : null;

                return label ? label.textContent.trim() : input.value;
            }).join(', ');
        }

        return field.value || '';
    }

    function updatePreview(row) {
        row.querySelectorAll('[data-preview-column]').forEach(cell => {
            var field = findField(row, cell.getAttribute('data-preview-column'));
            cell.textContent = fieldText(field);
        });
    }

    function captureSnapshot(row) {
        return Array.from(row.querySelectorAll('input, select, textarea')).map(field => {
            return {
                checked: field.checked,
                selected: field.tagName === 'SELECT' ? Array.from(field.options).map(option => option.selected) : [],
                type: field.type,
                value: field.type === 'file' ? null : field.value
            };
        });
    }

    function restoreSnapshot(row, snapshot) {
        restoringGroupSnapshot = tbody.closest('table').hasAttribute('data-sort-group-order');
        Array.from(row.querySelectorAll('input, select, textarea')).forEach((field, position) => {
            var state = snapshot[position];

            if (!state) {
                return;
            }

            if (field.type === 'file') {
                return;
            }

            if (field.type === 'checkbox' || field.type === 'radio') {
                field.checked = state.checked;
            } else if (field.tagName === 'SELECT') {
                Array.from(field.options).forEach((option, optionPosition) => {
                    option.selected = !!state.selected[optionPosition];
                });
            } else {
                field.value = state.value;
            }

            field.dispatchEvent(new Event('change', { bubbles: true }));
        });

        restoringGroupSnapshot = false;

        if (row._hasManyModalPosition) {
            var position = row._hasManyModalPosition;

            if (position.next && position.next.parentNode === tbody) {
                tbody.insertBefore(row, position.next);
            } else if (position.previous && position.previous.parentNode === tbody) {
                tbody.insertBefore(row, position.previous.nextSibling);
            } else {
                tbody.appendChild(row);
            }

            row._hasManyModalPosition = null;
        }

        updatePreview(row);
        if (tbody.closest('table').hasAttribute('data-sort-group-order')) {
            refreshOrderValues();
        }
    }

    function removeRequired(row) {
        row.querySelectorAll('[required]').forEach(field => {
            field.setAttribute('data-has-many-modal-required', '1');
            field.removeAttribute('required');
        });
    }

    function restoreRequired(row) {
        row.querySelectorAll('[data-has-many-modal-required="1"]').forEach(field => {
            field.setAttribute('required', 'required');
            field.removeAttribute('data-has-many-modal-required');
        });
    }

    function setRemoveFlag(row, value) {
        var removeFlag = row.querySelector('.{$removeClass}');

        if (removeFlag) {
            removeFlag.value = value;
        }
    }

    function rowIsRemoved(row) {
        var removeFlag = row.querySelector('.{$removeClass}');

        return removeFlag && removeFlag.value == '1';
    }

    function markDeleted(row) {
        setRemoveFlag(row, 1);
        removeRequired(row);
        row.classList.add('has-many-modal-table-deleted');
        row.querySelectorAll('.has-many-modal-edit').forEach(button => button.classList.add('d-none'));
        row.querySelectorAll('.has-many-modal-restore').forEach(button => button.classList.remove('d-none'));
        refreshOrderValues();
    }

    function restoreDeleted(row) {
        setRemoveFlag(row, 0);
        restoreRequired(row);
        row.classList.remove('has-many-modal-table-deleted');
        row.querySelectorAll('.has-many-modal-edit').forEach(button => button.classList.remove('d-none'));
        row.querySelectorAll('.has-many-modal-restore').forEach(button => button.classList.add('d-none'));
        refreshOrderValues();
    }

    function finalDelete(row) {
        if (row.getAttribute('data-has-many-row-new') === '1') {
            row.remove();
        } else {
            hide(row);
        }

        refreshOrderValues();
    }

    function activeRows() {
        if (!tbody) {
            return [];
        }

        return Array.from(tbody.children).filter(row => {
            return row.style.display !== 'none' && !rowIsRemoved(row);
        });
    }

    function groupValue(row) {
        if (!groupColumn) {
            return '';
        }

        var groupField = findField(row, groupColumn);

        return groupField ? groupField.value : '';
    }

    function refreshOrderValues() {
        if (!sortable) {
            return;
        }

        refreshGroupOrder();
        var counters = Object.create(null);

        activeRows().forEach(row => {
            var group = groupValue(row);
            counters[group] = counters[group] || 0;

            var sortField = findField(row, sortColumn);
            if (sortField) {
                sortField.value = counters[group];
            }

            var number = row.querySelector('.has-many-sort-number');
            if (number) {
                number.textContent = counters[group] + 1;
            }

            counters[group]++;
        });
    }

    function moveRow(row, direction) {
        var rows = activeRows();
        var currentIndex = rows.indexOf(row);
        var target = rows[currentIndex + direction];

        if (!target || groupValue(target) !== groupValue(row)) {
            return;
        }

        if (direction < 0) {
            tbody.insertBefore(row, target);
        } else {
            tbody.insertBefore(target, row);
        }

        refreshOrderValues();
    }

    function addEnterListener(el) {
        if (!el || el.getAttribute('data-has-many-enter-{$this->getJSSelector()}') === '1') {
            return;
        }

        el.setAttribute('data-has-many-enter-{$this->getJSSelector()}', '1');

        el.addEventListener('keydown', function (event) {
            if (event.keyCode == 13) {
                event.preventDefault();
                return false;
            }
        });
    }

    function initRow(row) {
        if (rowIsRemoved(row)) {
            removeRequired(row);
        }
        row.querySelectorAll('input').forEach(addEnterListener);
        row.querySelectorAll('.has-many-modal').forEach(modalEl => {
            if (modalEl.getAttribute('data-has-many-modal-bound') === '1') {
                return;
            }

            modalEl.setAttribute('data-has-many-modal-bound', '1');
            modalEl.addEventListener('hidden.bs.modal', function () {
                if (row._hasManyModalApplied) {
                    row._hasManyModalApplied = false;
                    return;
                }

                if (row._hasManyModalSnapshot) {
                    restoreSnapshot(row, row._hasManyModalSnapshot);
                    row._hasManyModalSnapshot = null;
                }
            });
        });

        updatePreview(row);
    }

    if (add) {
        add.addEventListener('click', function () {
            do {
                index++;
            } while (tbody.closest('table').hasAttribute('data-sort-group-order')
                && Array.from(tbody.children).some(row => row.getAttribute('data-has-many-row-key') === 'new_' + index));

            var tpl = hasMany.querySelector('template.{$this->id}-tpl').innerHTML;
            tpl = tpl.replace(/{$defaultKey}/g, index);
            var clone = htmlToElement(tpl);

            tbody.appendChild(clone);
            initRow(clone);

            {$templateScript}
            admin.form.cascade(clone);
            admin.form.jsonFields();
            refreshOrderValues();

            var modalEl = clone.querySelector('.has-many-modal');
            if (modalEl) {
                clone._hasManyModalSnapshot = captureSnapshot(clone);
                if (tbody.closest('table').hasAttribute('data-sort-group-order')) {
                    clone._hasManyModalPosition = { previous: clone.previousElementSibling, next: clone.nextElementSibling };
                }
                clone._hasManyModalApplied = false;
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }

            return false;
        });
    }

    hasMany.addEventListener('click', event => {
        if (!ownsGroupEvent(event)) {
            return;
        }

        var edit = event.target.closest('.has-many-modal-edit');
        var apply = event.target.closest('.has-many-modal-apply');
        var remove = event.target.closest('.has-many-modal-delete');
        var restore = event.target.closest('.has-many-modal-restore');
        var sortButton = event.target.closest('.has-many-sort-up, .has-many-sort-down');

        if (edit && hasMany.contains(edit)) {
            event.preventDefault();

            var editRow = edit.closest('.has-many-{$this->id}-form');
            var editModal = editRow ? editRow.querySelector('.has-many-modal') : null;

            if (editModal) {
                editRow._hasManyModalSnapshot = captureSnapshot(editRow);
                if (editRow.closest('table').hasAttribute('data-sort-group-order')) {
                    editRow._hasManyModalPosition = { previous: editRow.previousElementSibling, next: editRow.nextElementSibling };
                }
                editRow._hasManyModalApplied = false;
                bootstrap.Modal.getOrCreateInstance(editModal).show();
            }

            return;
        }

        if (apply && hasMany.contains(apply)) {
            event.preventDefault();

            var applyRow = apply.closest('.has-many-{$this->id}-form');
            var applyModal = apply.closest('.has-many-modal');

            if (applyRow) {
                applyRow._hasManyModalApplied = true;
                applyRow._hasManyModalSnapshot = null;
                applyRow._hasManyModalPosition = null;
                updatePreview(applyRow);
                refreshOrderValues();
            }

            if (applyModal) {
                bootstrap.Modal.getOrCreateInstance(applyModal).hide();
            }

            return;
        }

        if (remove && hasMany.contains(remove)) {
            event.preventDefault();

            var removeRow = remove.closest('.has-many-{$this->id}-form');

            if (removeRow) {
                if (rowIsRemoved(removeRow)) {
                    finalDelete(removeRow);
                } else {
                    markDeleted(removeRow);
                }
            }

            return;
        }

        if (restore && hasMany.contains(restore)) {
            event.preventDefault();

            var restoreRow = restore.closest('.has-many-{$this->id}-form');

            if (restoreRow) {
                restoreDeleted(restoreRow);
            }

            return;
        }

        if (sortButton && hasMany.contains(sortButton)) {
            event.preventDefault();

            var sortRow = sortButton.closest('.has-many-{$this->id}-form');

            if (sortRow) {
                moveRow(sortRow, sortButton.classList.contains('has-many-sort-up') ? -1 : 1);
            }
        }
    });

    hasMany.addEventListener('change', event => {
        if (!ownsGroupEvent(event)) {
            return;
        }

        var row = event.target.closest('.has-many-{$this->id}-form');

        if (row && event.target.matches('[name]')) {
            updatePreview(row);
        }

        if (!restoringGroupSnapshot && sortable && groupColumn && event.target.matches(columnSelector(groupColumn))) {
            refreshOrderValues();
        }
    });

    hasMany.querySelectorAll('.has-many-{$this->id}-form').forEach(initRow);

    if (sortable && (sortWith === 'drag' || sortWith === 'all') && typeof Sortable !== 'undefined') {
        new Sortable(tbody, {
            animation: 150,
            handle: '.handle',
            filter: function (event, target) {
                var row = target.closest('.has-many-{$this->id}-form');
                return row && rowIsRemoved(row);
            },
            onStart: captureGroupDrag,
            onMove: function (event) {
                return !rowIsRemoved(event.dragged) && !rowIsRemoved(event.related)
                    && groupValue(event.dragged) === groupValue(event.related);
            },
            onEnd: function (event) {
                finishGroupDrag(event);
                refreshOrderValues();
            }
        });
    }

    refreshOrderValues();
});

JS;

        Admin::script($script);
    }

    protected function getJSSelector()
    {
        return Str::camel($this->getElementClassString());

    }

    /**
     * Disable create button.
     *
     * @return $this
     */
    public function disableCreate()
    {
        $this->options['allowCreate'] = false;

        return $this;
    }

    /**
     * Disable delete button.
     *
     * @return $this
     */
    public function disableDelete()
    {
        $this->options['allowDelete'] = false;

        return $this;
    }

    /**
     * Render the `HasMany` field.
     *
     *
     * @return View
     *
     * @throws \Exception
     */
    public function render()
    {
        if (! $this->shouldRender()) {
            return '';
        }

        if (! in_array($this->viewMode, ['table', 'modalTable'], true)) {
            $this->addSortable('.has-many-', '-forms');
        }

        if ($this->viewMode == 'table') {
            return $this->renderTable();
        }

        if ($this->viewMode == 'modalTable') {
            return $this->renderModalTable();
        }

        // specify a view to render.
        $this->view = $this->views[$this->viewMode];

        [$template, $script] = $this->buildNestedForm($this->column, $this->builder)
            ->getTemplateHtmlAndScript();

        $this->setupScript($script);

        return parent::fieldRender([
            'forms' => $this->buildRelatedForms(),
            'template' => $template,
            'relationName' => $this->relationName,
            'verticalAlign' => $this->verticalAlign,
            'options' => $this->options,
        ]);
    }

    /**
     * Render the `HasMany` field for table style.
     *
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function renderTable()
    {
        $this->resolveSortGroupOrder();
        $headers = [];
        $fields = [];
        $hidden = [];
        $scripts = [];

        if (! $this->isSortableTable()) {
            $this->addSortable('.has-many-', '-forms');
        }

        /* @var Field $field */
        foreach ($this->buildNestedForm($this->column, $this->builder)->fields() as $i => $field) {
            if (is_a($field, Hidden::class)) {
                $hidden[] = $field->render();
            } else {
                /* Hide label and set field width 100% */
                $field->setLabelClass(['hidden']);
                //                $field->setWidth(12, 0);
                $fields[] = $field->render();
                $headers[] = $field->label();
            }

            /*
             * Get and remove the last script of Admin::$script stack.
             */
            if ($field->getScript()) {
                $scripts[] = array_pop(Admin::$script);
            }
        }

        /* Build row elements */
        $template = array_reduce($fields, function ($all, $field) {
            $all .= "<td>{$field}</td>";

            return $all;
        }, '');

        /* Build cell with hidden elements */
        $template .= '<td class="hidden">'.implode('', $hidden).'</td>';

        $forms = $this->buildRelatedForms();

        $this->setupScript(implode("\r\n", $scripts));

        // specify a view to render.
        $this->view = $this->views[$this->viewMode];

        $options = $this->sortableTableOptions();

        return parent::fieldRender([
            'headers' => $headers,
            'forms' => $forms,
            'template' => $template,
            'modalTablePresentKey' => static::MODAL_TABLE_PRESENT_KEY,
            'relationName' => $this->relationName,
            'verticalAlign' => $this->verticalAlign,
            'options' => $options,
        ]);
    }

    /**
     * Render the `HasMany` field for modal table style.
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function renderModalTable()
    {
        $this->resolveSortGroupOrder();
        $form = $this->buildNestedForm($this->column, $this->builder);
        $previewColumns = $this->modalTablePreviewColumns($form);

        [$template, $script] = $form->getTemplateHtmlAndScript();

        $forms = $this->buildRelatedForms();

        $this->setupScript($script);

        $this->view = $this->views[$this->viewMode];

        $options = $this->sortableTableOptions();

        return parent::fieldRender([
            'forms' => $forms,
            'previews' => $this->modalTablePreviews($forms, $previewColumns),
            'previewColumns' => $previewColumns,
            'template' => $template,
            'modalTablePresentKey' => static::MODAL_TABLE_PRESENT_KEY,
            'relationName' => $this->relationName,
            'verticalAlign' => $this->verticalAlign,
            'options' => $options,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function sortableTableOptions(): array
    {
        $options = array_merge($this->options, [
            'sortableHasManyTable' => $this->isSortableTable(),
            'removedRows' => $this->sortableRemovedRows,
        ]);

        if ($this->hasSortGroupOrder()) {
            $options['sortGroupOrder'] = json_encode([
                'priority' => $this->resolvedSortGroupOrder,
                'groups' => $this->sortableGroups,
            ], JSON_THROW_ON_ERROR);
        }

        return $options;
    }
}
