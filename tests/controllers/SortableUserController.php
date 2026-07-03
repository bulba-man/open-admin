<?php

namespace Tests\Controllers;

use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use Tests\Models\SortableUser;

class SortableUserController extends AdminController
{
    protected $title = 'Sortable Users';

    protected function grid()
    {
        $grid = new Grid(new SortableUser);

        $grid->id('ID');
        $grid->username();

        return $grid;
    }

    protected function form()
    {
        $form = new Form(new SortableUser);

        $form->text('username')->default('sortable-user');
        $form->email('email')->default('sortable@example.com');
        $form->password('password')->default('secret');

        if (request('standalone_table')) {
            $form->table('data', 'Data', function (Form\NestedForm $form) {
                $form->text('group');
                $form->text('title');
            })->sortable()->sortColumn('position')->sortGroupColumn('group');

            return $form;
        }

        $items = $form->hasMany('sortableItems', 'Items', function (Form\NestedForm $form) {
            $form->text('group');
            $form->text('title')->rules('required');
        })->sortable();

        if (request('view_mode') === 'modal_table') {
            $columns = request('modal_columns') === 'default' ? [] : ['group', 'title'];

            $items->useModalTable($columns);

            if (request('hide_delete_text')) {
                $items->hideDeleteText();
            }

            if (request('hide_delete_icon')) {
                $items->hideDeleteIcon();
            }

            if (request('hide_edit_text')) {
                $items->hideEditText();
            }

            if (request('hide_edit_icon')) {
                $items->hideEditIcon();
            }
        } elseif (request('view_mode') !== 'default') {
            $items->useTable();
        }

        if ($mode = request('sort_with')) {
            $items->sortWith($mode);
        }

        if ($column = request('sort_column')) {
            $items->sortColumn($column);
        }

        if (request('sort_group', 'group') !== 'none') {
            $items->sortGroupColumn(request('sort_group', 'group'));
        }

        if (request('sortable') === 'false') {
            $items->sortable(false);
        }

        return $form;
    }
}
