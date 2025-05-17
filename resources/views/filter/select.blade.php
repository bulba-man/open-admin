<select class="form-select {{ $class }}" name="{{$name}}" style="width: 100%;">
    @if($emptyOption)<option></option>@endif
    @foreach($options as $select => $option)
        <option value="{{$select}}" {{ (string)$select === (string)request($name, $value) ?'selected':'' }}>{{$option}}</option>
    @endforeach
</select>
