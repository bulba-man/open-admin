<?php

namespace OpenAdmin\Admin\Grid\Actions;

use Illuminate\Database\Eloquent\Model;
use OpenAdmin\Admin\Actions\Response;
use OpenAdmin\Admin\Actions\RowAction;

class Restore extends RowAction
{
    public $icon = 'icon-trash-restore';

    /**
     * @return array|null|string
     */
    public function name()
    {
        return trans('admin.restore');
    }

    public function dialog()
    {
        $options  = [
            "type" => "warning",
            "showCancelButton"=> true,
            "confirmButtonColor"=> "#00a65a",
            "confirmButtonText"=> trans('admin.confirm'),
            "showLoaderOnConfirm"=> true,
            "cancelButtonText"=>  trans('admin.cancel'),
        ];
        $this->confirm(trans('admin.restore_confirm'), '', $options);
    }

    /**
     * @param Model $model
     *
     * @return Response
     */
    public function handle(Model $model)
    {
        if (!$model->restore()) {
            return $this->response()->error(trans('admin.restore_failed'));
        }

        return $this->response()->success(trans('admin.restore_succeeded'))->refresh();
    }
}
