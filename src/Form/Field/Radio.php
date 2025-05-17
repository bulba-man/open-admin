<?php

namespace OpenAdmin\Admin\Form\Field;

use Illuminate\Contracts\Support\Arrayable;
use OpenAdmin\Admin\Form\Field;
use OpenAdmin\Admin\Form\Field\Interfaces\OptionSourceInterface;
use OpenAdmin\Admin\Form\Field\Traits\CanCascadeFields;

class Radio extends Field
{
    use CanCascadeFields;

    protected $stacked = false;

    /**
     * @var string
     */
    protected $cascadeEvent = 'change';

    /**
     * Set options.
     *
     * @param array|callable|string $options
     *
     * @return $this
     */
    public function options($options = [])
    {
        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        if (is_string($options)) {
            if (class_exists($options)) {
                $interfaces = class_implements($options);
                if (isset($interfaces[OptionSourceInterface::class])) {
                    /** @var OptionSourceInterface $class */
                    $class = new $options;
                    $this->options = $class->toOptionArray();

                    return $this;
                }
            }

            $arr = json_decode($options, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->options = $arr;

                return $this;
            }
        }

        $this->options = (array) $options;

        return $this;
    }

    /**
     * Set checked.
     *
     * @param array|callable|string $checked
     *
     * @return $this
     */
    public function checked($checked = [])
    {
        if ($checked instanceof Arrayable) {
            $checked = $checked->toArray();
        }

        // input radio checked should be unique
        $this->checked = is_array($checked) ? (array) end($checked) : (array) $checked;

        return $this;
    }

    /**
     * Draw inline radios.
     *
     * @return $this
     */
    public function inline()
    {
        $this->stacked = false;

        return $this;
    }

    /**
     * Draw stacked radios.
     *
     * @return $this
     */
    public function stacked()
    {
        $this->stacked = true;

        return $this;
    }

    /**
     * Set options.
     *
     * @param array|callable|string $values
     *
     * @return $this
     */
    public function values($values)
    {
        return $this->options($values);
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        //$this->script = "$('{$this->getElementClassSelector()}').iCheck({radioClass:'iradio_minimal-blue'});";

        $this->addCascadeScript();

        $this->addVariables(['options' => $this->options, 'checked' => $this->checked, 'stacked' => $this->stacked]);

        return parent::render();
    }
}
