<?php

namespace OpenAdmin\Admin\Grid\Filter\Presenter;

use OpenAdmin\Admin\Grid\Filter\AbstractFilter;

abstract class Presenter
{
    /**
     * @var AbstractFilter
     */
    protected $filter;

    /**
     * Set parent filter.
     *
     * @param AbstractFilter $filter
     */
    public function setParent(AbstractFilter $filter)
    {
        $this->filter = $filter;
    }

    /**
     * @see https://stackoverflow.com/questions/19901850/how-do-i-get-an-objects-unqualified-short-class-name
     *
     * @return string
     */
    public function view(): string
    {
        $reflect = new \ReflectionClass(get_called_class());

        return 'admin::filter.'.strtolower($reflect->getShortName());
    }

    /**
     * Set default value for filter.
     *
     * @param $default
     *
     * @return $this
     */
    public function default($default)
    {
        $this->filter->default($default);

        return $this;
    }

    /**
     * Set width for field and label.
     *
     * @param int $field
     * @param int $label
     *
     * @return $this
     */
    public function setWidth(int $field = 8, int $label = 2): self
    {
        $this->filter->setWidth($field, $label);

        return $this;
    }

    /**
     * Blade template variables for this presenter.
     *
     * @return array
     */
    public function variables(): array
    {
        return [];
    }
}
