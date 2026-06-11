<?php

namespace OpenAdmin\Admin\Form\Concerns;

use OpenAdmin\Admin\Form\Field;

trait HandleCascadeFields
{
    public function cascadeGroup(\Closure $closure, array $dependency)
    {
        $this->pushField(new Field\CascadeGroup($dependency));

        call_user_func($closure, $this);

        $this->html('</div>')->plain();
    }
}
