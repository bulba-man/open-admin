@include("admin::form._header")

    <div class="form-check form-switch">
        <input type="hidden" name="{{$name}}" id="{{$id}}" value="{{ old($column, $value) }}" />
        <input class="form-check-input {{$class}}" type="checkbox" id="{{$name}}_cb" {{ !empty(old($column, $value)) ? 'checked' : '' }} {!! $attributes !!} onchange="this.closest('.form-switch').querySelector('input[type=hidden]').value = (this.checked ? '{{$options['on']}}' : '{{$options['off']}}')" />
    </div>

@include("admin::form._footer")
