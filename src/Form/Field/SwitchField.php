<?php

namespace OpenAdmin\Admin\Form\Field;

use OpenAdmin\Admin\Form\Field;
use OpenAdmin\Admin\Form\Field\Traits\CanCascadeFields;

class SwitchField extends Field
{

    use CanCascadeFields;

    protected $cascadeEvent = 'change';

    protected $default_on_empty = 0;

    protected $options = [
        'on' => '1',
        'off' => '0',
    ];

    public function prepare($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = trim($value);

            if (empty($value)) {
                $value = $this->default_on_empty;
            } elseif (strtolower($value) === 'true' || strtolower($value) === 'false') {
                $value = filter_var(   $value, FILTER_VALIDATE_BOOLEAN);
            }
        }

        if (is_null($value)) {
            $value = $this->default_on_empty;
        }

        if (is_numeric($value)) {
            $value = filter_var(   $value, FILTER_VALIDATE_INT);
        }

        return parent::prepare($value);
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

        $this->addCascadeScript();

        return parent::render();
    }
}
