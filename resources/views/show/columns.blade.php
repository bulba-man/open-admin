@if($label)
    <div class="form-divider">
        <span class="divider-text">{{ $label }}</span>
    </div>
@endif

<div class="row">
    @foreach($columns as $column)
        <div class="{{ $column['class'] }}">
            @foreach($column['fields'] as $field)
                {!! $field->render() !!}
            @endforeach
        </div>
    @endforeach
</div>
