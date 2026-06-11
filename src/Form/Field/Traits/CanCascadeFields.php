<?php

namespace OpenAdmin\Admin\Form\Field\Traits;

use Illuminate\Support\Arr;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Form\Field\CascadeGroup;
use OpenAdmin\Admin\Form\Field\Checkbox;
use OpenAdmin\Admin\Form\Field\MultipleSelect;
use OpenAdmin\Admin\Form\Field\Radio;
use OpenAdmin\Admin\Form\Field\Select;
use OpenAdmin\Admin\Form\Field\SwitchField;

/**
 * @property Form $form
 */
trait CanCascadeFields
{
    /**
     * @var array
     */
    protected $conditions = [];

    /**
     * @return $this
     */
    public function when($operator, $value, $closure = null)
    {
        if (func_num_args() == 2) {
            $closure = $value;
            $value = $operator;
            $operator = '=';
        }

        $this->formatValues($operator, $value);

        $this->addDependents($operator, $value, $closure);

        return $this;
    }

    /**
     * @param  mixed  $value
     */
    protected function formatValues(string $operator, &$value)
    {
        if (in_array($operator, ['in', 'notIn', 'oneIn', 'oneNotIn'])) {
            $value = Arr::wrap($value);
        }

        if (is_array($value)) {
            $value = array_map('strval', $value);
        } else {
            $value = strval($value);
        }
    }

    /**
     * @param  mixed  $value
     */
    protected function addDependents(string $operator, $value, \Closure $closure)
    {
        $index = count($this->conditions);

        $this->conditions[] = compact('operator', 'value', 'closure');

        $this->getCascadeContainer()->cascadeGroup($closure, [
            'column' => $this->column(),
            'index' => $index,
            'class' => $this->getCascadeClass($value, $index),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function fill($data)
    {
        parent::fill($data);

        $this->applyCascadeConditions();
    }

    /**
     * @param  mixed  $value
     * @return string
     */
    protected function getCascadeClass($value, ?int $index = null)
    {
        $selector = $this->getElementClassSelector();
        if (is_array($selector)) {
            $selector = implode('|', $selector);
        }

        return 'cascade-group-'.md5($selector.'|'.$index.'|'.serialize($value));
    }

    /**
     * Apply conditions to dependents fields.
     *
     * @return void
     */
    protected function applyCascadeConditions()
    {
        $container = $this->getCascadeContainer();

        if ($container && method_exists($container, 'fields')) {
            $container->fields()
                ->filter(function (Form\Field $field) {
                    return $field instanceof CascadeGroup
                        && $field->dependsOn($this)
                        && $this->hitsCondition($field);
                })->each(function (CascadeGroup $field) {
                    $field->visiable();
                });
        }
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    protected function hitsCondition(CascadeGroup $group)
    {
        $condition = $this->conditions[$group->index()];

        extract($condition);

        $old = old($this->column(), $this->value());

        switch ($operator) {
            case '=':
                return $old == $value;
            case '>':
                return $old > $value;
            case '<':
                return $old < $value;
            case '>=':
                return $old >= $value;
            case '<=':
                return $old <= $value;
            case '!=':
                return $old != $value;
            case 'in':
                return in_array($old, $value);
            case 'notIn':
                return ! in_array($old, $value);
            case 'has':
                return in_array($value, (array) $old);
            case 'oneIn':
                return count(array_intersect($value, (array) $old)) >= 1;
            case 'oneNotIn':
                return count(array_intersect($value, (array) $old)) == 0;
            default:
                throw new \Exception("Operator [$operator] not support.");
        }
    }

    /**
     * Add cascade data to the source field.
     *
     * @return void
     */
    protected function addCascadeScript()
    {
        if (empty($this->conditions)) {
            return;
        }

        $cascadeGroups = collect($this->conditions)->map(function ($condition, $index) {
            return [
                'class' => $this->getCascadeClass($condition['value'], $index),
                'operator' => $condition['operator'],
                'value' => $condition['value'],
            ];
        })->values()->toJson();

        $attributes = [
            'data-cascade-event' => $this->cascadeEvent,
            'data-cascade-groups' => $cascadeGroups,
            'data-cascade-type' => $this->getCascadeInputType(),
            'data-cascade-selector' => $this->getElementClassSelector(),
        ];

        if ($this->getCascadeInputType() === 'switch') {
            $attributes['data-cascade-switch-on'] = $this->options['on'];
            $attributes['data-cascade-switch-off'] = $this->options['off'];
        }

        $this->attribute($attributes);
    }

    /**
     * @return string
     */
    protected function getCascadeInputType()
    {
        if ($this instanceof SwitchField) {
            return 'switch';
        }

        if ($this instanceof Radio) {
            return 'radio';
        }

        if ($this instanceof Checkbox) {
            return 'checkbox';
        }

        if ($this instanceof MultipleSelect) {
            return 'select-multiple';
        }

        if ($this instanceof Select) {
            return 'select';
        }

        throw new \InvalidArgumentException('Invalid form field type');
    }
}
