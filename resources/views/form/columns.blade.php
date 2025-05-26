@if($label)
    @include("admin::form._header")
@endif
<div class="row {{$class}}">
    @foreach($columns as $column)
        <div class="{{$column['class']}}">
            @foreach($column['fields'] as $field)
                {!! $field->render() !!}
            @endforeach
        </div>
    @endforeach
</div>
@if($label)
    @include("admin::form._footer")
@endif
