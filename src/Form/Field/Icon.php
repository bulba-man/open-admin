<?php

namespace OpenAdmin\Admin\Form\Field;

class Icon extends Text
{
    protected $default = '';

    protected static $js = [
        '/vendor/open-admin/fields/icon-picker/icon-picker.js',
    ];

    public function render()
    {
        $showClass = str_replace(['[', ']'], '-', $this->getElementName());
        $this->script = <<<JS

new Iconpicker(document.querySelector('[name="{$this->getElementName()}"]'),{
    showSelectedIn: document.querySelector(".{$showClass}-icon"),
    defaultValue: '{$this->value}',
});
JS;

        $this->prepend('<span class="'.$showClass.'-icon"><i class="'.$this->value.'"></i></span>');
        $this->style('max-width', '160px');

        return parent::render();
    }
}
