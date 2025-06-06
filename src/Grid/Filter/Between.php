<?php

namespace OpenAdmin\Admin\Grid\Filter;

use Illuminate\Support\Arr;
use OpenAdmin\Admin\Admin;

class Between extends AbstractFilter
{
    /**
     * {@inheritdoc}
     */
    protected $view = 'admin::filter.between';

    protected $placeholder = ['start' => '', 'end' => ''];

    protected $icon = '';

    public function __construct($column, $label = '')
    {
        parent::__construct($column, $label);

        $this->placeholder = ['start' => $this->label, 'end' => $this->label];
    }

    /**
     * Format id.
     *
     * @param string $column
     *
     * @return array|string
     */
    public function formatId($column)
    {
        $id = str_replace('.', '_', $column);

        return ['start' => "{$id}_start", 'end' => "{$id}_end"];
    }

    public function setPlaceholders($start, $end)
    {
        $this->placeholder = ['start' => $start, 'end' => $end];
    }


    /**
     * Format two field names of this filter.
     *
     * @param string $column
     *
     * @return array
     */
    protected function formatName($column)
    {
        $columns = explode('.', $column);

        if (count($columns) == 1) {
            $name = $columns[0];
        } else {
            $name = array_shift($columns);

            foreach ($columns as $column) {
                $name .= "[$column]";
            }
        }

        return ['start' => "{$name}[start]", 'end' => "{$name}[end]"];
    }

    /**
     * Get condition of this filter.
     *
     * @param array $inputs
     *
     * @return mixed
     */
    public function condition($inputs)
    {
        if ($this->ignore) {
            return;
        }

        if (!Arr::has($inputs, $this->column)) {
            return;
        }

        $this->value = Arr::get($inputs, $this->column);

        $value = array_filter($this->value, function ($val) {
            return $val !== '';
        });

        if (empty($value)) {
            return;
        }

        if (!isset($value['start'])) {
            return $this->buildCondition($this->column, '<=', $value['end']);
        }

        if (!isset($value['end'])) {
            return $this->buildCondition($this->column, '>=', $value['start']);
        }

        $this->query = 'whereBetween';

        return $this->buildCondition($this->column, $this->value);
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function datetime($options = [])
    {
        $this->view = 'admin::filter.betweenDatetime';
        $this->icon = (isset($options['icon'])) ? $options['icon'] : 'icon-calendar';

        $this->setupDatetime($options);

        return $this;
    }

    /**
     * @param array $options
     */
    protected function setupDatetime($options = [])
    {
        $options['format'] = Arr::get($options, 'format', 'YYYY-MM-DD HH:mm:ss');
        $options['locale'] = Arr::get($options, 'locale', config('app.locale'));
        $options['allowInput'] = Arr::get($options, 'allowInput', true);

        $script = Admin::makeFlatpickrInit('#'.$this->id['start'], $options, "inst_{$this->id['start']}");
        $script .= Admin::makeFlatpickrInit('#'.$this->id['end'], $options + ['useCurrent' => false], "inst_{$this->id['end']}");
        $script .= <<<SCRIPT

        inst_{$this->id['start']}.config.onChange.push(function(selectedDates, dateStr, instance) {
            inst_{$this->id['end']}.set("minDate",dateStr);
        });

        inst_{$this->id['end']}.config.onChange.push(function(selectedDates, dateStr, instance) {
            inst_{$this->id['start']}.set("maxDate",dateStr);
        });

SCRIPT;

        Admin::script($script);
    }

    protected function variables()
    {
        return array_merge(parent::variables(), [
            'placeholder' => $this->placeholder,
            'icon' => $this->icon,
        ]);
    }
}
