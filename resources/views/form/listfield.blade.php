
@php($listErrorKey = "$column")
@include("admin::form._header")

        <div data-list-field>
        <table class="table table-with-fields">

            <tbody class="list-{{$class}}-table" data-list-field-table>

            @foreach(old("{$column}", ($value ?: [])) as $k => $v)

                @php($itemErrorKey = "{$column}.{$loop->index}")

                <tr>
                    @if(!empty($options['sortable']))
                        <td width="20"><span class="icon-arrows-alt-v btn btn-light handle"></span></td>
                    @endif
                    <td>
                        <div class="form-group {{ $errors->has($itemErrorKey) ? 'has-error' : '' }}">
                            <div class="col-sm-12">
                                <input name="{{ $name }}[]" value="{{ old("{$column}.{$k}", $v) }}" class="form-control" />
                                @if($errors->has($itemErrorKey))
                                    @foreach($errors->get($itemErrorKey) as $message)
                                        <div class="text-danger" ><i class="icon-times-circle-o"></i> {{$message}}</div><br/>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </td>

                    <td style="width: 75px;">
                        <div class="{{$class}}-remove btn btn-danger btn-sm pull-right" data-list-field-remove>
                            <i class="icon-trash">&nbsp;</i>{{ __('admin.remove') }}
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="{{ $class }}-add btn btn-success btn-sm pull-right" data-list-field-add>
            <i class="icon-plus"></i>&nbsp;{{ __('admin.new') }}
        </div>

        <template class="{{$class}}-tpl" data-list-field-template>
            <tr>
                @if(!empty($options['sortable']))
                    <td width="20"><span class="icon-arrows-alt-v btn btn-light handle"></span></td>
                @endif
                <td>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <input name="{{ $name }}[]" class="form-control" />
                        </div>
                    </div>
                </td>

                <td style="width: 75px;">
                    <div class="{{$class}}-remove btn btn-danger btn-sm pull-right" data-list-field-remove>
                        <i class="icon-trash">&nbsp;</i>{{ __('admin.remove') }}
                    </div>
                </td>
            </tr>
        </template>
        </div>

@include("admin::form._footer")
