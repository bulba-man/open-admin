@include("admin::form._header")

            @if(empty(!$inline))
            <div class="radio-as-list-label">{{$label}}</div>
            @endif
            <ul class="list-group radio-as-list">
            @foreach($options as $option => $label)
                <li class="list-group-item">
                    <input type="radio" name="{{$name}}" value="{{$option}}" id="{{$name}}-{{$option}}" class="btn-check {{$class}}" {{ ($option == old($column, $value)) || ($value === null && in_array($label, $checked)) ?'checked':'' }} {!! $attributes !!} />
                    <label class="btn" for="{{$name}}-{{$option}}">{{$label}}</label>
                </li>
            @endforeach
            </ul>

@include("admin::form._footer")
