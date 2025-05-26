<?php

namespace OpenAdmin\Admin\Form\Field;

use OpenAdmin\Admin\Form\Field;

class Divider extends Field
{
    protected $title;

    public function __construct($title = '')
    {
        $this->title = $title;
    }

    public function render()
    {
        if (!$this->shouldRender()) {
            return '';
        }

        if (empty($this->title)) {
            return '<hr>';
        }

        return <<<HTML
<div class="form-divider">
  <span class="divider-text">
    {$this->title}
  </span>
</div>
HTML;
    }
}
