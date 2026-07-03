@include("admin::form._header")

        <div id="has-many-{{$id}}">
            <table class="table table-with-fields has-many-{{$id}} vertical-align-{{$verticalAlign}}"
                @if(!empty($options['sortableHasManyTable']))
                    data-sort-column="{{ $options['sortColumn'] }}"
                    data-sort-group-column="{{ $options['sortGroupColumn'] }}"
                    data-sort-with="{{ $options['sortWith'] }}"
                @endif
            >
                <thead>
                <tr>
                    @if(!empty($options['sortable']))
                        <th></th>
                    @endif

                    @foreach($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach

                    <th class="hidden"></th>

                    @if($options['allowDelete'])
                        <th></th>
                    @endif
                </tr>
                </thead>
                <tbody class="has-many-{{$id}}-forms">
                @foreach($forms as $pk => $form)
                    <tr class="has-many-{{$id}}-form fields-group">

                        @if(!empty($options['sortable']))
                            <td width="20">
                                @if(!empty($options['sortableHasManyTable']))
                                    @if(in_array($options['sortWith'], ['drag', 'all'], true))
                                        <span class="icon-arrows-alt-v btn btn-light handle"></span>
                                    @endif

                                    @if(in_array($options['sortWith'], ['buttons', 'all'], true))
                                        <button type="button" class="btn btn-light btn-sm has-many-sort-up">
                                            <i class="icon-arrow-up"></i>
                                        </button>
                                        <button type="button" class="btn btn-light btn-sm has-many-sort-down">
                                            <i class="icon-arrow-down"></i>
                                        </button>
                                    @endif
                                @else
                                    <span class="icon-arrows-alt-v btn btn-light handle"></span>
                                @endif
                            </td>
                        @endif

                        <?php $hidden = ''; ?>

                        @foreach($form->fields() as $field)

                            @if (is_a($field, \OpenAdmin\Admin\Form\Field\Hidden::class))
                                <?php $hidden .= $field->render(); ?>
                                @continue
                            @endif

                            <td>{!! $field->setLabelClass(['hidden'])->setWidth(12, 0)->render() !!}</td>
                        @endforeach

                        <td class="hidden">{!! $hidden !!}</td>

                        @if($options['allowDelete'])
                            <td class="form-group">
                                <div>
                                    <div class="remove btn btn-danger btn-sm pull-right">@include('admin::form._add_delete_button', ['type' => 'delete', 'defaultText' => trans('admin.remove'), 'defaultIcon' => 'icon-trash'])</div>
                                </div>
                            </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>

            <template class="{{$id}}-tpl">
                    <tr class="has-many-{{$id}}-form fields-group">

                    @if(!empty($options['sortable']))
                        <td width="20">
                            @if(!empty($options['sortableHasManyTable']))
                                @if(in_array($options['sortWith'], ['drag', 'all'], true))
                                    <span class="icon-arrows-alt-v btn btn-light handle"></span>
                                @endif

                                @if(in_array($options['sortWith'], ['buttons', 'all'], true))
                                    <button type="button" class="btn btn-light btn-sm has-many-sort-up">
                                        <i class="icon-arrow-up"></i>
                                    </button>
                                    <button type="button" class="btn btn-light btn-sm has-many-sort-down">
                                        <i class="icon-arrow-down"></i>
                                    </button>
                                @endif
                            @else
                                <span class="icon-arrows-alt-v btn btn-light handle"></span>
                            @endif
                        </td>
                    @endif

                    {!! $template !!}

                    @if($options['allowDelete'])
                        <td class="form-group">
                            <div>
                                <div class="remove btn btn-danger btn-sm pull-right">@include('admin::form._add_delete_button', ['type' => 'delete', 'defaultText' => trans('admin.remove'), 'defaultIcon' => 'icon-trash'])</div>
                            </div>
                        </td>
                    @endif
                </tr>
            </template>

            @if($options['allowCreate'])
                <div class="form-group">
                    <div class="{{$viewClass['field']}}">
                        <div class="add btn btn-success btn-sm">@include('admin::form._add_delete_button', ['type' => 'add', 'defaultText' => trans('admin.new'), 'defaultIcon' => 'icon-plus'])</div>
                    </div>
                </div>
            @endif
        </div>
@include("admin::form._footer")
