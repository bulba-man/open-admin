
<div class="row has-many-head {{$id}}">
    <h4>{{ $label }}</h4>
</div>

<hr class="form-border">

<div id="has-many-{{$id}}" class="has-many-body has-many-{{$id}}">

    <div class="has-many-{{$id}}-forms">

        @foreach($forms as $pk => $form)

            <div class="has-many-{{$id}}-form fields-group">

                @foreach($form->fields() as $field)
                    {!! $field->render() !!}
                @endforeach

                @if($options['allowDelete'])
                <div class="form-group form-delete-group">
                    <label class="{{$viewClass['label']}} form-label"></label>
                    <div class="{{$viewClass['field']}}">
                        @if($options['deleteButShowText'])
                        <div class="remove btn btn-danger btn-sm pull-right"><i class="icon-trash">&nbsp;</i>{{ trans('admin.remove') }}</div>
                        @else
                        <div class="remove btn btn-danger btn-sm pull-right"><i class="icon-trash"></i></div>
                        @endif
                    </div>
                </div>
                @endif
                <hr class="form-border">
            </div>


        @endforeach
    </div>


    <template class="{{$id}}-tpl">
        <div class="has-many-{{$id}}-form fields-group">

            {!! $template !!}

            <div class="form-group form-delete-group">
                <label class="{{$viewClass['label']}} form-label"></label>
                <div class="{{$viewClass['field']}}">
                    @if($options['deleteButShowText'])
                    <div class="remove btn btn-danger btn-sm pull-right"><i class="icon-trash"></i>&nbsp;{{ trans('admin.remove') }}</div>
                    @else
                    <div class="remove btn btn-danger btn-sm pull-right"><i class="icon-trash"></i></div>
                    @endif
                </div>
            </div>
            <hr class="form-border">

        </div>
    </template>

    @if($options['allowCreate'])
    <div class="has-many-footer form-group">
        <label class="{{$viewClass['label']}} form-label"></label>
        <div class="{{$viewClass['field']}}">
            @if($options['addButShowText'])
            <div class="add btn btn-success btn-sm"><i class="icon-save"></i>&nbsp;{{ trans('admin.new') }}</div>
            @else
            <div class="add btn btn-success btn-sm"><i class="icon-save"></i></div>
            @endif
        </div>
    </div>
    @endif

</div>
