@include("admin::form._header")

        @if (!empty($attributes_obj['readonly']))
        <input type="hidden" name="{{$name}}" value="{{$value}}" />
        @endif

        <select class="form-select {{$class}}" name="{{$name}}@if (!empty($attributes_obj['readonly']))-disabled @endif" {!! $attributes !!} >
            @if($emptyOption)<option value=""></option>@endif
            @if($groups)
                @foreach($groups as $group)
                    <optgroup label="{{ $group['label'] }}">
                        @foreach($group['options'] as $select => $option)
                            <option value="{{$select}}" {{ $select == old($column, $value) ?'selected':'' }}>{{$option}}</option>
                        @endforeach
                    </optgroup>
                @endforeach
             @else
                @foreach($options as $select => $option)
                    <option value="{{$select}}" {{ $select == old($column, $value) ?'selected':'' }}>{{$option}}</option>
                @endforeach
            @endif
        </select>

@include("admin::form._footer")
