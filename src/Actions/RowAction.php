<?php

namespace OpenAdmin\Admin\Actions;

use Illuminate\Http\Request;
use Mockery\Matcher\Closure;
use OpenAdmin\Admin\Grid\Column;

abstract class RowAction extends GridAction
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $row;

    /**
     * @var Column
     */
    protected $column;

    /**
     * @var string
     */
    public $selectorPrefix = '.grid-row-action-';

    /**
     * @var bool
     */
    protected $asColumn = false;

    /**
     * @var Closure
     */
    protected $beforeRenderCollback;

    /**
     * @var string
     */
    protected $cssClass = '';

    /**
     * Get primary key value of current row.
     *
     * @return mixed
     */
    protected function getKey()
    {
        return $this->row->getKey();
    }

    /**
     * Set row model.
     *
     * @param mixed $key
     *
     * @return \Illuminate\Database\Eloquent\Model|mixed
     */
    public function row($key = null)
    {
        if (func_num_args() == 0) {
            return $this->row;
        }

        return $this->row->getAttribute($key);
    }

    /**
     * Set row model.
     *
     * @param \Illuminate\Database\Eloquent\Model $row
     *
     * @return $this
     */
    public function setRow($row)
    {
        $this->row = $row;

        return $this;
    }

    public function getRow()
    {
        return $this->row;
    }

    /**
     * @param Column $column
     *
     * @return $this
     */
    public function setColumn(Column $column)
    {
        $this->column = $column;

        return $this;
    }

    /**
     * Show this action as a column.
     *
     * @return $this
     */
    public function asColumn()
    {
        $this->asColumn = true;

        return $this;
    }

    /**
     * @return string
     */
    public function href()
    {
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function retrieveModel(Request $request)
    {
        if (!$key = $request->get('_key')) {
            return false;
        }

        $modelClass = str_replace('_', '\\', $request->get('_model'));

        if ($this->modelUseSoftDeletes($modelClass)) {
            return $modelClass::withTrashed()->findOrFail($key);
        }

        return $modelClass::findOrFail($key);
    }

    public function display($value)
    {
    }

    public function beforeRender(\Closure $callback)
    {
        $this->beforeRenderCollback = $callback;
    }

    public function shouldRender(): bool
    {
        if ($this->beforeRenderCollback instanceof \Closure) {
            return $this->beforeRenderCollback->call($this, $this);
        }

        return true;
    }

    /**
     * Render row action.
     *
     * @return string
     */
    public function render()
    {
        if (!$this->shouldRender()) {
            return '';
        }

        $linkClass = ($this->parent->getActionClass() != "OpenAdmin\Admin\Grid\Displayers\Actions\Actions") ? 'dropdown-item' : '';
        $icon = $this->getIcon();

        if ($href = $this->href()) {
            return "<a href='{$href}' class='{$linkClass} {$this->cssClass}' title='{$this->name()}'>{$icon}<span class='label'>{$this->name()}</span></a>";
        }

        $this->addScript();

        $attributes = $this->formatAttributes();

        return sprintf(
            "<a data-_key='%s' href='javascript:void(0);' class='%s {$linkClass} {$this->cssClass}' title='{$this->name()}' {$attributes}>{$icon}<span class='label'>%s</span></a>",
            $this->getKey(),
            $this->getElementClass(),
            $this->asColumn ? $this->display($this->row($this->column->getName())) : $this->name()
        );
    }
}
