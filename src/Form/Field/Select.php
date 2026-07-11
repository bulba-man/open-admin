<?php

namespace OpenAdmin\Admin\Form\Field;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use OpenAdmin\Admin\Form\Field;
use OpenAdmin\Admin\Form\Field\Interfaces\OptionSourceInterface;
use OpenAdmin\Admin\Form\Field\Traits\CanCascadeFields;

class Select extends Field
{
    use CanCascadeFields;

    /**
     * @var array
     */
    protected $groups = [];

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var string
     */
    protected $cascadeEvent = 'change';

    /**
     * @var bool
     */
    protected $native = false;

    public $additional_script = '';

    protected $fieldWidth = '100%';

    /**
     * @var bool
     */
    protected $emptyOption = true;

    /**
     * Set options.
     *
     * @param  array|callable|string  $options
     * @return $this|mixed
     */
    public function options($options = [])
    {
        // remote options
        if (is_string($options)) {
            // reload selected
            if (class_exists($options)) {
                if (in_array(Model::class, class_parents($options))) {
                    return $this->model(...func_get_args());
                }

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

            $this->options = [];

            return $this->loadRemoteOptions(...func_get_args());
        }

        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        if (is_callable($options)) {
            $this->options = $options;
        } else {
            $this->options = (array) $options;
        }

        return $this;
    }

    /**
     * Set option groups.
     *
     * eg: $group = [
     *        [
     *        'label' => 'xxxx',
     *        'options' => [
     *            1 => 'foo',
     *            2 => 'bar',
     *            ...
     *        ],
     *        ...
     *     ]
     *
     *
     * @return $this
     */
    public function groups(array $groups)
    {
        $this->groups = $groups;

        return $this;
    }

    /**
     * Load options for other select on change.
     *
     * @param  string  $field
     * @param  string  $sourceUrl
     * @param  string  $idField
     * @param  string  $textField
     * @return $this
     */
    public function load($field, $url, $idField = 'id', $textField = 'text', bool $allowClear = true)
    {
        $this->additional_script .= <<<JS
        (function () {
            let elm = {$this->choicesObjName()}?.passedElement?.element;
            if (!elm) {
                return;
            }
            var lookupTimeout;
            elm.addEventListener('change', function(event) {
                var query = {$this->choicesObjName()}.getValue()?.value;
                var current_value = {$this->choicesObjName($field)}.getValue()?.value;
                admin.ajax.post("{$url}",{query:query},function(data){
                    if (current_value) {
                        let found = false;
                        for (i in data.data){
                            if (data.data[i].id == current_value){
                                data.data[i].selected = true;
                                found = true;
                            }
                        }
                        if (!found){
                            data.data.push({'{$idField}':'','{$textField}':'','selected':true});
                        }
                    }

                    {$this->choicesObjName($field)}.setChoices(data.data, '{$idField}', '{$textField}', true);

                    {$this->choicesObjName($field)}.passedElement.element.dispatchEvent(
                    new CustomEvent('choices:loaded', {
                        bubbles: true,
                        detail: { count: data.length },
                      })
                    );
                })
            });
            })();
JS;

        return $this;
    }

    /**
     * Load options from current selected resource(s).
     *
     * @param  string  $model
     * @param  string  $idField
     * @param  string  $textField
     * @return $this
     */
    public function model($model, $idField = 'id', $textField = 'name')
    {
        if (! class_exists($model)
            || ! in_array(Model::class, class_parents($model))
        ) {
            throw new \InvalidArgumentException("[$model] must be a valid model class");
        }

        $this->options = function ($value) use ($model, $idField, $textField) {
            if (empty($value)) {
                return [];
            }

            $resources = [];

            if (is_array($value)) {
                if (Arr::isAssoc($value)) {
                    $resources[] = Arr::get($value, $idField);
                } else {
                    $resources = array_column($value, $idField);
                }
            } else {
                $resources[] = $value;
            }

            return $model::find($resources)->pluck($textField, $idField)->toArray();
        };

        return $this;
    }

    /**
     * Load options from remote.
     *
     * @param  string  $url
     * @param  array  $parameters
     * @param  array  $options
     * @return $this
     */
    protected function loadRemoteOptions($url, $parameters = [], $options = [])
    {
        $this->config = array_merge([
            'removeItems' => true,
            'removeItemButton' => true,
            'allowHTML' => true,
        ], $this->config);

        if (! isset($parameters['selected']) && ! is_null($this->value())) {
            $parameters['selected'] = $this->value();
        }

        $parameters_json = json_encode($parameters);

        $this->additional_script .= <<<JS
        (function () {
        if (!{$this->choicesObjName()}) {
            return;
        }
        admin.ajax.post("{$url}",{$parameters_json},function(data){
            {$this->choicesObjName()}.setChoices(data.data, 'id', 'text', true);
        });
        })();
JS;

        return $this;
    }

    /**
     * Load options from ajax results.
     *
     * @param  string  $url
     * @return $this
     */
    public function ajax($url, $idField = 'id', $textField = 'text')
    {
        $this->config = array_merge([
            'removeItems' => true,
            'removeItemButton' => true,
            'allowHTML' => true,
            'placeholder' => $this->label,
        ], $this->config);

        $this->additional_script = <<<JS
        (function () {
            let elm = {$this->choicesObjName()}?.passedElement?.element;
            if (!elm) {
                return;
            }
            var lookupTimeout;
            elm.addEventListener('search', function(event) {
                clearTimeout(lookupTimeout);
                lookupTimeout = setTimeout(function(){
                    var query = {$this->choicesObjName()}.input.value;
                    admin.ajax.post("{$url}",{query:query},function(data){
                        {$this->choicesObjName()}.setChoices(data.data, '{$idField}', '{$textField}', true);
                    })
                }, 250);
            });

            elm.addEventListener('choice', function(event) {
                {$this->choicesObjName()}.setChoices([], '{$idField}', '{$textField}', true);
            });
            })();
        JS;

        return $this;
    }

    /**
     * Set use browser native selectbox.
     *
     * @return $this
     */
    public function useNative()
    {
        $this->native = true;

        return $this;
    }

    /**
     * Set selectbox without empty option.
     *
     * @return $this
     */
    public function hideEmpty(): static
    {
        $this->emptyOption = false;

        return $this;
    }

    /**
     * Set use browser native selectbox.
     *
     * @return $this
     */
    public function useChoicesjs()
    {
        $this->native = false;

        return $this;
    }

    /**
     * Set config for Choicesjs.
     *
     * all configurations see https://github.com/jshjohnson/Choices
     *
     * @param  string  $key
     * @param  mixed  $val
     * @return $this
     */
    public function config($key, $val)
    {
        $this->config[$key] = $val;

        return $this;
    }

    /**
     * Set as readonly (actual dissable with backup hidden field).
     */
    public function readonly($set = true): self
    {
        $this->useNative();
        $this->config('readonly', $set);
        $this->disabled($set);

        return parent::readonly($set);
    }

    /**
     * Returns variable name for ChoicesJS object.
     */
    public function choicesObjName($field = false)
    {
        if (empty($field)) {
            $field = str_replace([' ', '-'], ['_', '_'], $this->getElementClassString());
        }

        return 'choices_'.$field;
    }

    /**
     * Check if field should be rendered as Choises JS (not the case if fields are embed in popup).
     */
    public function allowedChoicesJs()
    {
        $class = get_class($this);

        return in_array($class, [
            'OpenAdmin\Admin\Form\Field\Select',
            'OpenAdmin\Admin\Form\Field\Tags',
            'OpenAdmin\Admin\Form\Field\MultipleSelect',
            'OpenAdmin\Admin\Form\Field\Timezone',
        ]);
    }

    public function width($width): Field
    {
        $this->fieldWidth = $width;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        parent::width($this->fieldWidth);

        if ($this->options instanceof \Closure) {
            if ($this->form) {
                $this->options = $this->options->bindTo($this->form->model());
            }
            $this->options(call_user_func($this->options, $this->value, $this));
        }

        $this->options = array_filter($this->options, 'strlen');

        $configs = array_merge([
            'removeItems' => true,
            'removeItemButton' => true,
            'allowHTML' => true,
            'placeholder' => [
                'id' => '',
                'text' => $this->label,
            ],
            'classNames' => [
                'containerOuter' => ['choices', $this->getElementClass()],
            ],
            'loadingText' => trans('admin.choices.loadingText'),
            'noResultsText' => trans('admin.choices.noResultsText'),
            'noChoicesText' => trans('admin.choices.noChoicesText'),
            'itemSelectText' => trans('admin.choices.itemSelectText'),
            'uniqueItemText' => trans('admin.choices.uniqueItemText'),
            'customAddItemText' => trans('admin.choices.customAddItemText'),
        ], $this->config);
        $configs = json_encode($configs);

        if (! $this->native && $this->allowedChoicesJs()) {
            $selector = json_encode($this->getElementClassSelector());
            $choicesObjName = $this->choicesObjName();
            $objectName = json_encode($choicesObjName);
            $this->script .= <<<JS
var {$choicesObjName} = (function (container) {
    var selector = {$selector};
    var options = {$configs};
    var objectName = {$objectName};

    if (window.admin && admin.form && typeof admin.form.choices === 'function') {
        return admin.form.choices(selector, options, objectName, container);
    }

    if (typeof Choices !== 'function') {
        return null;
    }

    container = container || document;

    var fields = [];

    if (container.matches && container.matches(selector)) {
        fields.push(container);
    }

    container.querySelectorAll(selector).forEach(function (field) {
        fields.push(field);
    });

    var choice = null;

    fields.forEach(function (field) {
        if (field.dataset.choicesInitialized === '1') {
            choice = field.choicesInstance || choice;
            return;
        }

        field.dataset.choicesInitialized = '1';
        choice = new Choices(field, options);
        field.choicesInstance = choice;
    });

    if (objectName) {
        if (!window.choices_vars) {
            window.choices_vars = [];
        }

        window.choices_vars[objectName] = choice;
    }

    return choice;
})(typeof clone !== 'undefined' ? clone : document);
JS;
            $this->script .= $this->additional_script;

            $this->attribute('data-choices-obj-name', $choicesObjName);
        }

        $this->addVariables([
            'options' => $this->options,
            'groups' => $this->groups,
            'emptyOption' => $this->emptyOption,
        ]);

        $this->addCascadeScript();

        $this->attribute('data-value', implode(',', (array) $this->value()));

        return parent::render();
    }
}
