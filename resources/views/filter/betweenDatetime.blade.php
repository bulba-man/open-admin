<div class="form-group row">
    <label class="col-sm-2 control-label">{{$label}}</label>
    <div class="col-sm-8" style="width: 390px">
        <div class="input-group">
            @if($icon)
                <div class="input-group-text">
                    <i class="{{$icon}}"></i>
                </div>
            @endif
            <input type="text"
                   class="form-control"
                   id="{{$id['start']}}"
                   placeholder="{{$placeholder['start']}}"
                   name="{{$name['start']}}"
                   value="{{ request()->input("{$column}.start", \Illuminate\Support\Arr::get($value, 'start')) }}"
                   autocomplete="off"
            />

            <span class="input-group-text" style="border-left: 0; border-right: 0;">-</span>

            <input type="text"
                   class="form-control"
                   id="{{$id['end']}}"
                   placeholder="{{$placeholder['end']}}"
                   name="{{$name['end']}}"
                   value="{{ request()->input("{$column}.end", \Illuminate\Support\Arr::get($value, 'end')) }}"
                   autocomplete="off"
            />
        </div>
    </div>
</div>
