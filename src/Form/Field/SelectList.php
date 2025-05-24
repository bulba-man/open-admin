<?php

namespace OpenAdmin\Admin\Form\Field;

class SelectList extends Select
{
    /**
     * @var string[]
     */
    protected $elementClass = ['select-as-list'];

    /**
     * @var int
     */
    protected $size = 5;

    /**
     * @var bool
     */
    protected $useNative = true;

    /**
     * @var bool
     */
    protected $emptyOption = false;

    /**
     * View for field to render.
     *
     * @var string
     */
    protected $view = 'admin::form.select';

    public function size(int $size)
    {
        $this->size = $size;

        return $this;
    }

    public function render()
    {
        $this->attribute('size', $this->size);

        return parent::render();
    }
}
