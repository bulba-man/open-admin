<?php

namespace OpenAdmin\Admin\Form\Field;

use Closure;
use Illuminate\Support\Collection;
use OpenAdmin\Admin\Form;

class Columns extends \OpenAdmin\Admin\Form\Field
{
    /**
     * @var Collection
     */
    protected $columns;

    public function __construct($label = '')
    {
        $this->columns = new Collection();
        $this->label = $label;
    }

    public function add($width, Closure $content)
    {
        $fields = $this->collectFields($content);

        $offset = 0;
        if (is_array($width) && count($width) > 1) {
            $offset = $width[1];
            $width = $width[0];
        }

        $this->columns->push(compact('width', 'offset', 'fields'));

        return $this;
    }

    protected function collectFields(Closure $content)
    {
        $currentFieldsCount = $this->form->fields()->count();

        call_user_func($content, $this->form);

        $fields = clone $this->form->fields();
        $fields = $fields->slice($currentFieldsCount);

        return $fields;
    }

    public function variables(): array
    {
        $this->variables = array_merge($this->variables, [
            'columns' => $this->columns
        ]);

        return parent::variables();
    }

    public function render()
    {
        $result = parent::render();

        foreach ($this->columns as $column) {
            foreach ($column['fields'] as $field) {
                $field->setDisplay(false);
            }
        }

        return $result;
    }
}
