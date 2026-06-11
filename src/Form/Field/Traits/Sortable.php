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

                document.querySelectorAll('{$pref}{$this->id}{$suf}').forEach((sortableElement) => {
                    if (sortableElement.getAttribute('data-sortable-initialized') === '1') {
                        return;
                    }

                    sortableElement.setAttribute('data-sortable-initialized', '1');

                    new Sortable(sortableElement, {
                    animation:150,
                    handle: ".handle"
                });
                });
            JS;
            Admin::script($script);
        }
    }
}
