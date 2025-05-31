@include("admin::form._header")

        <div class="btn-group grey-border" role="group">
            @foreach($options as $option => $label)
                <input type="radio" name="{{$name}}" value="{{$option}}" id="{{$id}}-{{$option}}" class="btn-check {{$class}}" {{ ($option == old($column, $value)) || ($value === null && in_array($label, $checked)) ?'checked':'' }} {!! $attributes !!} />
                <label class="btn btn-outline-primary" for="{{$id}}-{{$option}}">{{$label}}</label>
            @endforeach
        </div>

@include("admin::form._footer")
