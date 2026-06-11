
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
                        <div class="remove btn btn-danger btn-sm pull-right">@include('admin::form._add_delete_button', ['type' => 'delete', 'defaultText' => trans('admin.remove'), 'defaultIcon' => 'icon-trash'])</div>
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
                    <div class="remove btn btn-danger btn-sm pull-right">@include('admin::form._add_delete_button', ['type' => 'delete', 'defaultText' => trans('admin.remove'), 'defaultIcon' => 'icon-trash'])</div>
                </div>
            </div>
            <hr class="form-border">

        </div>
    </template>

    @if($options['allowCreate'])
    <div class="has-many-footer form-group">
        <label class="{{$viewClass['label']}} form-label"></label>
        <div class="{{$viewClass['field']}}">
            <div class="add btn btn-success btn-sm">@include('admin::form._add_delete_button', ['type' => 'add', 'defaultText' => trans('admin.new'), 'defaultIcon' => 'icon-save'])</div>
        </div>
    </div>
    @endif

</div>
