<?php

namespace OpenAdmin\Admin\Form\Field\Traits;

use OpenAdmin\Admin\Admin;

trait Sortable
{
    /**
     * Set sortable.
     *
     * @return $this
     */
    public function sortable($set = true)
    {
        $this->options['sortable'] = $set;

        return $this;
    }

    public function addSortable($pref = '', $suf = '')
    {
        if (isset($this->options['sortable'])) {
            $script = <<<JS

                var sortable = new Sortable(document.querySelector('{$pref}{$this->id}{$suf}'), {
                    animation:150,
                    handle: ".handle"
                });
            JS;
            Admin::script($script);
        }
    }
}
