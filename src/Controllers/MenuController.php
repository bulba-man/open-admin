<?php

namespace OpenAdmin\Admin\Controllers;

use Illuminate\Routing\Controller;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Layout\Column;
use OpenAdmin\Admin\Layout\Content;
use OpenAdmin\Admin\Layout\Row;
use OpenAdmin\Admin\Tree;
use OpenAdmin\Admin\Widgets\Box;

class MenuController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->title(trans('admin.menu'))
            ->description(trans('admin.list'))
            ->row(function (Row $row) {
                $row->column(6, $this->treeView()->render());

                $row->column(6, function (Column $column) {
                    $form = new \OpenAdmin\Admin\Widgets\Form();
                    $form->action(admin_url('auth/menu'));

                    $menuModel = config('admin.database.menu_model');
                    $permissionModel = config('admin.database.permissions_model');
                    $roleModel = config('admin.database.roles_model');

                    $form->select('parent_id', trans('admin.parent_id'))->options($menuModel::selectOptions())->setWidth(7, 3);
                    $form->text('title', trans('admin.title'))->rules('required')->setWidth(7, 3);
                    $form->text('slug', trans('admin.slug'))->help(trans('admin.menu_form.slug_help'))->setWidth(7, 3);
                    $form->icon('icon', trans('admin.icon'))->default('fa-bars')->rules('required')->help($this->iconHelp())->setWidth(7, 3);
                    $form->text('classes', trans('admin.css_classes'))->setWidth(7, 3);
                    $form->text('uri', trans('admin.uri'))->setWidth(7, 3);
                    $form->switch('ajax', trans('admin.open_by_ajax'))->setWidth(7, 3)->default(1);
                    $form->multipleSelect('roles', trans('admin.roles'))->options($roleModel::all()->pluck('name', 'id'))->setWidth(7, 3);
                    if ((new $menuModel())->withPermission()) {
                        $form->select('permission', trans('admin.permission'))->options($permissionModel::pluck('name', 'slug'))->setWidth(7, 3);
                    }
                    $form->hidden('_token')->default(csrf_token());

                    $column->append((new Box(trans('admin.new'), $form))->style('success'));
                });
            });
    }

    /**
     * Redirect to edit page.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show($id)
    {
        return redirect()->route('admin.auth.menu.edit', ['menu' => $id]);
    }

    /**
     * @return \OpenAdmin\Admin\Tree
     */
    protected function treeView()
    {
        $menuModel = config('admin.database.menu_model');

        $tree = new Tree(new $menuModel());

        $tree->disableCreate();

        $tree->branch(function ($branch) {
            $payload = "<i class='{$branch['icon']}'></i>&nbsp;<strong>{$branch['title']}</strong>";

            if (!isset($branch['children'])) {
                if (url()->isValidUrl($branch['uri'])) {
                    $uri = $branch['uri'];
                } else {
                    $uri = admin_url($branch['uri']);
                }

                $payload .= "&nbsp;&nbsp;&nbsp;<a href=\"$uri\" class=\"dd-nodrag\">$uri</a>";
            }

            return $payload;
        });

        return $tree;
    }

    /**
     * Edit interface.
     *
     * @param string  $id
     * @param Content $content
     *
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->title(trans('admin.menu'))
            ->description(trans('admin.edit'))
            ->row($this->form()->edit($id));
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    public function form()
    {
        $menuModel = config('admin.database.menu_model');
        $permissionModel = config('admin.database.permissions_model');
        $roleModel = config('admin.database.roles_model');

        $form = new Form(new $menuModel());

        $form->display('id', 'ID');

        $form->select('parent_id', trans('admin.parent_id'))->options($menuModel::selectOptions());
        $form->text('title', trans('admin.title'))->rules('required');
        $form->text('slug', trans('admin.slug'))->help(trans('admin.menu_form.slug_help'));
        $form->icon('icon', trans('admin.icon'))->default('fa-bars')->rules('required')->help($this->iconHelp());
        $form->text('classes', trans('admin.css_classes'));
        $form->text('uri', trans('admin.uri'));
        $form->switch('ajax', trans('admin.open_by_ajax'))->default(1);
        $form->multipleSelect('roles', trans('admin.roles'))->options($roleModel::all()->pluck('name', 'id'));
        if ($form->model()->withPermission()) {
            $form->select('permission', trans('admin.permission'))->options($permissionModel::pluck('name', 'slug'));
        }

        $form->display('created_at', trans('admin.created_at'));
        $form->display('updated_at', trans('admin.updated_at'));

        return $form;
    }

    /**
     * Help message for icon field.
     *
     * @return string
     */
    protected function iconHelp()
    {
        return 'For more icons please see <a href="http://fontawesome.io/icons/" target="_blank">http://fontawesome.io/icons/</a>';
    }
}
