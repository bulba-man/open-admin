@include('admin::form.help-block')
@if(!empty($inline))
    </div>
@endif
@if (!empty($show_as_section))
    <hr class="form-border">
@endif
</div>
@if($isResettable)
    <div class="{{$viewClass['reset']}}">
        <div class="form-check form-check-inline">
            @php
                $checked = false;
                if (is_array($value) && is_array($defaultOnNull)) {
                    $diffAB = array_diff($value, $defaultOnNull);
                    $diffBA = array_diff($defaultOnNull, $value);
                    if (!count($diffAB) && !count($diffBA)) {
                        $checked = true;
                    }
                } else {
                    $checked = $value == $defaultOnNull;
                }

            @endphp

            @php $defaultOnNull = (is_array($defaultOnNull)) ? json_encode($defaultOnNull) : $defaultOnNull; @endphp
            @php $currentValue = (is_array($value)) ? json_encode($value) : $value; @endphp

            <input type="checkbox" class="form-check-input reset-field-to-default" id="{{$id}}-use_default_value_chk"
                   name="{{$resettableName}}"
                   value="1"
                   data-elem-class="{{$class}}"
                   data-default-value="{{$defaultOnNull}}"
                   data-current-value="{{$currentValue}}"
                   {{($checked) ? 'checked' : '' }}
            />
            <label class="form-check-label" for="{{$id}}-use_default_value_chk">Default</label>
        </div>
    </div>
@endif

