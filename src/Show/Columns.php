<?php

namespace OpenAdmin\Admin\Show;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use OpenAdmin\Admin\Show;

class Columns extends Field
{
    /**
     * @var Collection
     */
    protected $columns;

    public function __construct($label = '')
    {
        parent::__construct('', $label);

        $this->view = 'admin::show.columns';
        $this->columns = new Collection;
    }

    /**
     * @param  int|array|string  $width
     * @return $this
     */
    public function add($width, Closure $content): self
    {
        $fields = $this->collectFields($content);

        if (is_string($width)) {
            $class = $width;
        } else {
            $offset = 0;
            if (is_array($width) && count($width) > 1) {
                $offset = $width[1];
                $width = $width[0];
            }

            $class = 'col-sm-'.$width;
            if ($offset) {
                $class .= ' offset-sm-'.$offset;
            }
        }

        $this->columns->push(compact('width', 'class', 'fields'));

        return $this;
    }

    /**
     * Set parent show instance.
     *
     * @return $this
     */
    public function setParent(Show $show)
    {
        parent::setParent($show);

        return $this;
    }

    /**
     * Set value for nested fields.
     *
     * @return $this
     */
    public function setValue(Model $model)
    {
        foreach ($this->columns as $column) {
            foreach ($column['fields'] as $field) {
                $field->setValue($model);
            }
        }

        return $this;
    }

    /**
     * Set width for nested fields.
     *
     * @return $this
     */
    public function setWidth($field = 8, $label = 2)
    {
        foreach ($this->columns as $column) {
            foreach ($column['fields'] as $showField) {
                $showField->setWidth($field, $label);
            }
        }

        return $this;
    }

    protected function collectFields(Closure $content): Collection
    {
        $currentFieldsCount = $this->parent->getFields()->count();

        call_user_func($content, $this->parent);

        $fields = clone $this->parent->getFields();

        return $fields->slice($currentFieldsCount);
    }

    protected function variables()
    {
        return [
            'columns' => $this->columns,
            'label' => $this->getLabel(),
        ];
    }

    /**
     * Render this field.
     *
     * @return string
     */
    public function render()
    {
        if (! $this->shouldRender()) {
            return '';
        }

        $result = view($this->view, $this->variables())->render();

        foreach ($this->columns as $column) {
            foreach ($column['fields'] as $field) {
                $field->setDisplay(false);
            }
        }

        return $result;
    }
}
