<?php

namespace OpenAdmin\Admin\Form\Field;

use OpenAdmin\Admin\Form\Field;

class CascadeGroup extends Field
{
    /**
     * @var array
     */
    protected $dependency;

    /**
     * @var string
     */
    protected $hide = 'd-none';

    /**
     * CascadeGroup constructor.
     */
    public function __construct(array $dependency)
    {
        $this->dependency = $dependency;
    }

    /**
     * @return bool
     */
    public function dependsOn(Field $field)
    {
        return $this->dependency['column'] == $field->column();
    }

    /**
     * @return int
     */
    public function index()
    {
        return $this->dependency['index'];
    }

    /**
     * @return void
     */
    public function visiable()
    {
        $this->hide = '';
    }

    /**
     * @return string
     */
    public function render()
    {
        if (! $this->shouldRender()) {
            return '';
        }

        $class = e($this->dependency['class']);

        return <<<HTML
<div class="cascade-group {$class} {$this->hide}">
HTML;
    }

    /**
     * @return void
     */
    public function end()
    {
        $this->getCascadeContainer()->html('</div>')->plain();
    }
}
