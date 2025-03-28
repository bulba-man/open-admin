@include('admin::form.help-block')
@if(!empty($inline))
    </div>
@endif
@if (!empty($show_as_section))
    <hr class="form-border">
@endif
</div>
@if($resettable)
    <div class="{{$viewClass['reset']}}">
        <div class="form-check form-check-inline">
            @php
                $checked = false;
                if (is_array($value) && is_array($defaultValue)) {
                    $diffAB = array_diff($value, $defaultValue);
                    $diffBA = array_diff($defaultValue, $value);
                    if (!count($diffAB) && !count($diffBA)) {
                        $checked = true;
                    }
                } else {
                    $checked = $value == $defaultValue;
                }

            @endphp

            @php $defaultValue = (is_array($defaultValue)) ? json_encode($defaultValue) : $defaultValue; @endphp
            @php $currentValue = (is_array($value)) ? json_encode($value) : $value; @endphp

            <input type="checkbox" class="form-check-input reset-field-to-default" id="{{$id}}-use_default_value_chk"
                   name="{{$name}}inherit"
                   value="1"
                   data-elem-class="{{$class}}"
                   data-default-value="{{$defaultValue}}"
                   data-current-value="{{$currentValue}}"
                   {{($checked) ? 'checked' : '' }}
            />
            <label class="form-check-label" for="{{$id}}-use_default_value_chk">Default</label>
        </div>
    </div>
@endif

