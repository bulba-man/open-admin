<?php

namespace OpenAdmin\Admin\Grid\Tools;

use OpenAdmin\Admin\Actions\BatchAction;
use Illuminate\Database\Eloquent\Collection;

class BatchRestore extends BatchAction
{
    public $icon = 'icon-trash-restore';

    public function __construct()
    {
        parent::__construct();

        $this->name = trans('admin.batch_restore');
    }

    public function handle(Collection $collection)
    {
        $collection->each->restore();

        return $this->response()->success(trans('admin.restore_succeeded'))->refresh();
    }

    public function dialog()
    {
        $options  = [
            "type" => "warning",
            "showCancelButton"=> true,
            "confirmButtonColor"=> "#00a65a",
            "confirmButtonText"=> __('admin.confirm'),
            "showLoaderOnConfirm"=> true,
            "cancelButtonText"=>  __('admin.cancel'),
        ];
        $this->confirm(__('admin.restore_confirm'), '', $options);
    }
}
