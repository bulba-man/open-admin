@include("admin::form._header")

        <div id="has-many-{{$id}}" class="has-many-modal-table">
            <input type="hidden" name="{{$name}}[{{$modalTablePresentKey}}]" value="1">

            <table class="table has-many-{{$id}} vertical-align-{{$verticalAlign}}"
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

                    @foreach($previewColumns as $column)
                        <th>{{ $column['label'] }}</th>
                    @endforeach

                    <th class="hidden"></th>
                    <th>{{ trans('admin.action') }}</th>
                </tr>
                </thead>
                <tbody class="has-many-{{$id}}-forms">
                @foreach($forms as $pk => $form)
                    @php
                        $rowErrorKeys = [];

                        foreach ($form->fields() as $field) {
                            foreach ((array) $field->getErrorKey() as $errorKey) {
                                $rowErrorKeys[] = $errorKey;
                            }
                        }

                        $rowHasErrors = collect($rowErrorKeys)->contains(function ($errorKey) use ($errors) {
                            return $errors->has($errorKey);
                        });
                        $rowKey = (string) $pk;
                        $rowId = preg_replace('/[^A-Za-z0-9_-]/', '_', $rowKey);
                        $modalId = "has-many-{$id}-modal-{$rowId}";
                        $rowIsNew = \Illuminate\Support\Str::startsWith($rowKey, 'new_');
                    @endphp

                    <tr class="has-many-{{$id}}-form fields-group has-many-modal-table-row {{ $rowHasErrors ? 'has-many-modal-table-error' : '' }}"
                        data-has-many-row-key="{{ $rowKey }}"
                        data-has-many-row-new="{{ $rowIsNew ? '1' : '0' }}"
                    >
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

                        @foreach($previewColumns as $column)
                            <td data-preview-column="{{ $column['column'] }}">{{ $previews[$pk][$column['column']] ?? '' }}</td>
                        @endforeach

                        <td class="hidden has-many-modal-table-fields">
                            <div class="modal has-many-modal" tabindex="-1" role="dialog" id="{{ $modalId }}">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h4 class="modal-title">{{ $label }}</h4>
                                            <button type="button" class="btn btn-light close has-many-modal-cancel" data-bs-dismiss="modal" aria-label="{{ trans('admin.cancel') }}"><span aria-hidden="true">&times;</span></button>
                                        </div>
                                        <div class="modal-body form form-horizontal">
                                            @foreach($form->fields() as $field)
                                                {!! $field->render() !!}
                                            @endforeach
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light has-many-modal-cancel" data-bs-dismiss="modal">{{ trans('admin.cancel') }}</button>
                                            <button type="button" class="btn btn-primary has-many-modal-apply">{{ trans('admin.apply') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="form-group has-many-modal-table-actions">
                            <button type="button" class="btn btn-primary btn-sm has-many-modal-edit">
                                @include('admin::form._add_delete_button', ['type' => 'edit', 'options' => $options, 'defaultText' => trans('admin.edit'), 'defaultIcon' => 'icon-edit'])
                            </button>

                            @if($options['allowDelete'])
                                <button type="button" class="btn btn-danger btn-sm has-many-modal-delete">
                                    @include('admin::form._add_delete_button', ['type' => 'delete', 'options' => $options, 'defaultText' => trans('admin.delete'), 'defaultIcon' => 'icon-trash'])
                                </button>
                                <button type="button" class="btn btn-light btn-sm has-many-modal-restore d-none">
                                    @include('admin::form._add_delete_button', ['type' => 'delete', 'options' => $options, 'defaultText' => trans('admin.restore'), 'defaultIcon' => 'icon-undo'])
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <template class="{{$id}}-tpl">
                <tr class="has-many-{{$id}}-form fields-group has-many-modal-table-row"
                    data-has-many-row-key="new_{{ \OpenAdmin\Admin\Form\NestedForm::DEFAULT_KEY_NAME }}"
                    data-has-many-row-new="1"
                >
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

                    @foreach($previewColumns as $column)
                        <td data-preview-column="{{ $column['column'] }}"></td>
                    @endforeach

                    <td class="hidden has-many-modal-table-fields">
                        <div class="modal has-many-modal" tabindex="-1" role="dialog" id="has-many-{{$id}}-modal-new_{{ \OpenAdmin\Admin\Form\NestedForm::DEFAULT_KEY_NAME }}">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title">{{ $label }}</h4>
                                        <button type="button" class="btn btn-light close has-many-modal-cancel" data-bs-dismiss="modal" aria-label="{{ trans('admin.cancel') }}"><span aria-hidden="true">&times;</span></button>
                                    </div>
                                    <div class="modal-body form form-horizontal">
                                        {!! $template !!}
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light has-many-modal-cancel" data-bs-dismiss="modal">{{ trans('admin.cancel') }}</button>
                                        <button type="button" class="btn btn-primary has-many-modal-apply">{{ trans('admin.apply') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>

                    <td class="form-group has-many-modal-table-actions">
                        <button type="button" class="btn btn-primary btn-sm has-many-modal-edit">
                            @include('admin::form._add_delete_button', ['type' => 'edit', 'options' => $options, 'defaultText' => trans('admin.edit'), 'defaultIcon' => 'icon-edit'])
                        </button>

                        @if($options['allowDelete'])
                            <button type="button" class="btn btn-danger btn-sm has-many-modal-delete">
                                @include('admin::form._add_delete_button', ['type' => 'delete', 'options' => $options, 'defaultText' => trans('admin.delete'), 'defaultIcon' => 'icon-trash'])
                            </button>
                            <button type="button" class="btn btn-light btn-sm has-many-modal-restore d-none">
                                @include('admin::form._add_delete_button', ['type' => 'delete', 'options' => $options, 'defaultText' => trans('admin.restore'), 'defaultIcon' => 'icon-undo'])
                            </button>
                        @endif
                    </td>
                </tr>
            </template>

            @if($options['allowCreate'])
                <div class="form-group">
                    <div class="{{$viewClass['field']}}">
                        <div class="add btn btn-success btn-sm">@include('admin::form._add_delete_button', ['type' => 'add', 'options' => $options, 'defaultText' => trans('admin.new'), 'defaultIcon' => 'icon-plus'])</div>
                    </div>
                </div>
            @endif
        </div>
@include("admin::form._footer")
