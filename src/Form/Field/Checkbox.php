<?php

namespace OpenAdmin\Admin\Form\Field;

use Illuminate\Contracts\Support\Arrayable;
use OpenAdmin\Admin\Form\Field\Interfaces\OptionSourceInterface;

class Checkbox extends MultipleSelect
{
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
     * @return $this|mixed
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

        if (is_callable($options)) {
            $this->options = $options;
        } else {
            $this->options = (array) $options;
        }

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

        $this->checked = (array) $checked;

        return $this;
    }

    /**
     * Draw inline checkboxes.
     *
     * @return $this
     */
    public function inline()
    {
        $this->stacked = false;

        return $this;
    }

    /**
     * Draw stacked checkboxes.
     *
     * @return $this
     */
    public function stacked()
    {
        $this->stacked = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $this->addVariables([
            'checked'      => $this->checked,
            'stacked'      => $this->stacked,
        ]);

        return parent::render();
    }
}
