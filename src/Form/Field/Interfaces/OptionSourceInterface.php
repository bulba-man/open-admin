<?php

namespace OpenAdmin\Admin\Form\Field\Interfaces;

/**
 *  Use fo fields with options
 */
interface OptionSourceInterface
{
    /**
     * Must be return value => label array
     * ['val' => 'Option', 5 => "Five"]
     *
     * @return array
     */
    public function toOptionArray():array;
}
