<div class="form-group row">
    <label class="col-sm-{{$width['label']}} control-label"> {{$label}}</label>
    <div class="col-sm-{{$width['field']}}">
        @include($presenter->view())
    </div>
</div>
