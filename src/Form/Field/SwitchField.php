<?php

namespace OpenAdmin\Admin\Form\Field;

use OpenAdmin\Admin\Form\Field;
use OpenAdmin\Admin\Form\Field\Traits\CanCascadeFields;

class SwitchField extends Field
{

    use CanCascadeFields;

    protected $cascadeEvent = 'change';

    protected $options = [
        'on' => '1',
        'off' => '0',
    ];

    public function prepare($value)
    {
        $value = trim($value);

        if (strtolower($value) === 'true' || strtolower($value) === 'false') {
            $value = filter_var(   $value, FILTER_VALIDATE_BOOLEAN);
        } elseif (is_numeric($value)) {
            $value = filter_var(   $value, FILTER_VALIDATE_INT);
        }

        return $value;
    }

    public function values(string $on = '1', string $off = '0'): static
    {
        $this->options = [
            'on' => $on,
            'off' => $off,
        ];

        return $this;
    }

    public function variables(): array
    {
        $this->variables = array_merge($this->variables, [
            'options'         => $this->options
        ]);

        return parent::variables();
    }

    public function render()
    {
        if (!$this->shouldRender()) {
            return '';
        }

        return parent::render();
    }
}
