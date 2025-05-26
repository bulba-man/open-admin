<?php

namespace OpenAdmin\Admin\Form\Field;

use Illuminate\Support\Arr;
use OpenAdmin\Admin\Admin;
use OpenAdmin\Admin\Form\Field;
use OpenAdmin\Admin\Form\Field\Traits\Sortable;

class ListField extends Field
{
    use Sortable;
    /**
     * @var array
     */
    protected $value = [''];

    /**
     * Fill data to the field.
     *
     * @param array $data
     *
     * @return void
     */
    public function fill($data)
    {
        $this->data = $data;

        $this->value = Arr::get($data, $this->column, $this->value);
        if (!is_array($this->value)) {
            $this->value = json_decode($this->value);
        }
        if (empty($this->value)) {
            $this->value = [''];
        }

        $this->formatValue();
    }

    /**
     * {@inheritdoc}
     */
    public function getValidator(array $input)
    {
        if ($this->validator) {
            return $this->validator->call($this, $input);
        }

        if (!is_string($this->column)) {
            return false;
        }

        $rules = $attributes = [];

        if (!$fieldRules = $this->getRules()) {
            return false;
        }

        if (!Arr::has($input, $this->column)) {
            return false;
        }

        $rules["{$this->column}.*"] = $fieldRules;
        $attributes["{$this->column}.*"] = __('Value');

        $rules["{$this->column}"][] = 'array';

        $attributes["{$this->column}"] = $this->label;

        return validator($input, $rules, $this->getValidationMessages(), $attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function setupScript()
    {
        $selector = str_replace(' ', '.', $this->getElementClassString());
        $this->script = <<<JS

        document.querySelector('.{$selector}-add').addEventListener('click', function () {
            addNewElementToList_{$selector}();
        });

        document.querySelector('tbody.list-{$selector}-table').addEventListener('click', function (event) {
            if (event.target.classList.contains('{$selector}-remove')){
                event.target.closest('tr').remove();
            }
        });

        document.querySelectorAll('tbody.list-{$selector}-table input').forEach(elem => {
            addEnterListFieldListener_{$selector}(elem);
            addPasteListFieldListener_{$selector}(elem);
        });

        function addNewElementToList_{$selector}() {
            var tpl = document.querySelector('template.{$selector}-tpl').innerHTML;
            var clone = htmlToElement(tpl);
            clone.querySelectorAll('input').forEach(elem => {
                addEnterListFieldListener_{$selector}(elem);
                addPasteListFieldListener_{$selector}(elem);
            });
            document.querySelector('tbody.list-{$selector}-table').appendChild(clone);
            var input = clone.querySelector('input');
            input.focus();
            input.setSelectionRange(-1, -1);

            return input;
        }

        function addPasteListFieldListener_{$selector}(el){
            el.addEventListener('paste', function (e) {
                var clipboardData, pastedData;
                e.stopPropagation();
                e.preventDefault();
                clipboardData = e.clipboardData || window.clipboardData;
                pastedData = clipboardData.getData('Text');
                var regexp = new RegExp("\\r\\n|\\r|\\n");
                var rows = pastedData.split(regexp);
                if (rows.length) {
                    e.target.value = rows[0];
                }

                if (rows.length > 1) {
                    for (var i = 1; i < rows.length; i++) {
                        var input = addNewElementToList_{$selector}();
                        input.value = rows[i];
                    }
                }
            });
        }

        function addEnterListFieldListener_{$selector}(el){
            el.addEventListener("keydown", function (event) {
                /** Enter **/
                if (event.keyCode == 13) {
                   event.preventDefault();
                    var parent = event.target.closest('tr');
                    var next = parent.nextElementSibling;
                    if (next && next.nodeName === 'TR') {
                        var input = next.querySelector('input');
                        input.focus();
                        input.setSelectionRange(-1, -1);
                        return false;
                    }
                    addNewElementToList_{$selector}();
                    return false;
                }

                /** Delete **/
                if (event.keyCode == 46) {
                    if (!event.target.value.length) {
                        event.preventDefault();
                        var parent = event.target.closest('tr');
                        var prev = parent.previousElementSibling;
                        parent.remove();
                        if (prev && prev.nodeName === 'TR') {
                            var input = prev.querySelector('input');
                            input.focus();
                            input.setSelectionRange(-1, -1);
                        }
                        return false;
                    }
                }

            });
        }
JS;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare($value)
    {
        $value = (array) parent::prepare($value);

        $values = array_values($value);
        if (count($values) == 1 && empty($values[0])) {
            return [];
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $this->addSortable('tbody.list-', '-table');
        view()->share('options', $this->options);

        $this->setupScript();

        Admin::style('td .form-group {margin-bottom: 0 !important;}');

        return parent::render();
    }
}
