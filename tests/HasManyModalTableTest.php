<?php

use OpenAdmin\Admin\Auth\Database\Administrator;
use Tests\Models\SortableItem;
use Tests\Models\SortableUser;

class HasManyModalTableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(Administrator::first(), 'admin');
    }

    public function test_modal_table_renders_preview_columns_actions_modal_and_template()
    {
        $this->visit('admin/sortable-users/create?view_mode=modal_table');

        $html = $this->response->getContent();

        $this->assertStringContainsString('class="has-many-modal-table"', $html);
        $this->assertStringNotContainsString('table-with-fields has-many-sortableItems', $html);
        $this->assertStringContainsString('data-sort-column="order"', $html);
        $this->assertStringContainsString('data-sort-group-column="group"', $html);
        $this->assertStringContainsString('data-preview-column="group"', $html);
        $this->assertStringContainsString('data-preview-column="title"', $html);
        $this->assertStringContainsString('name="sortableItems[__modal_table_present__]"', $html);
        $this->assertStringContainsString('has-many-modal-edit', $html);
        $this->assertStringContainsString('has-many-modal-delete', $html);
        $this->assertStringContainsString('has-many-modal-restore d-none', $html);
        $this->assertStringContainsString('has-many-modal-apply', $html);
        $this->assertStringContainsString('Apply', $html);
        $this->assertStringContainsString('has-many-sort-up', $html);
        $this->assertStringContainsString('name="sortableItems[new___key__][order]"', $html);
        $this->assertSame(1, substr_count($html, 'name="sortableItems[new___key__][order]"'));
    }

    public function test_modal_table_delete_and_restore_buttons_respect_button_visibility_options()
    {
        $this->visit('admin/sortable-users/create?view_mode=modal_table&hide_delete_text=1');

        $html = $this->response->getContent();

        $this->assertStringContainsString('<i class="icon-trash"></i>', $html);
        $this->assertStringContainsString('<i class="icon-undo"></i>', $html);
        $this->assertStringNotContainsString('Delete', $html);
        $this->assertStringNotContainsString('Restore', $html);

        $this->visit('admin/sortable-users/create?view_mode=modal_table&hide_delete_icon=1');

        $html = $this->response->getContent();

        $this->assertStringContainsString('Delete', $html);
        $this->assertStringContainsString('Restore', $html);
        $this->assertStringNotContainsString('<i class="icon-trash"></i>', $html);
        $this->assertStringNotContainsString('<i class="icon-undo"></i>', $html);
    }

    public function test_modal_table_edit_button_respects_button_visibility_options()
    {
        $this->visit('admin/sortable-users/create?view_mode=modal_table&hide_edit_text=1');

        $html = $this->response->getContent();

        $this->assertStringContainsString('<i class="icon-edit"></i>', $html);
        $this->assertStringNotContainsString('Edit', $html);

        $this->visit('admin/sortable-users/create?view_mode=modal_table&hide_edit_icon=1');

        $html = $this->response->getContent();

        $this->assertStringContainsString('Edit', $html);
        $this->assertStringNotContainsString('<i class="icon-edit"></i>', $html);
    }

    public function test_empty_modal_table_columns_fall_back_to_non_hidden_fields()
    {
        $this->visit('admin/sortable-users/create?view_mode=modal_table&modal_columns=default');

        $html = $this->response->getContent();

        $this->assertStringContainsString('data-preview-column="group"', $html);
        $this->assertStringContainsString('data-preview-column="title"', $html);
        $this->assertStringNotContainsString('data-preview-column="order"', $html);
        $this->assertStringNotContainsString('data-preview-column="id"', $html);
        $this->assertStringNotContainsString('data-preview-column="_remove_"', $html);
    }

    public function test_persisted_rows_render_escaped_preview_values_and_hidden_fields()
    {
        $user = $this->createSortableUser();

        $item = $user->sortableItems()->create([
            'group' => 'main',
            'order' => 0,
            'title' => '<b>Unsafe</b>',
        ]);

        $this->visit("admin/sortable-users/{$user->id}/edit?view_mode=modal_table");

        $html = $this->response->getContent();

        $this->assertStringContainsString('&lt;b&gt;Unsafe&lt;/b&gt;', $html);
        $this->assertStringContainsString("data-has-many-row-key=\"{$item->id}\"", $html);
        $this->assertStringContainsString("name=\"sortableItems[{$item->id}][id]\"", $html);
        $this->assertStringContainsString("name=\"sortableItems[{$item->id}][_remove_]\"", $html);
    }

    public function test_modal_table_sortable_submission_is_normalized_by_visible_order()
    {
        $user = $this->createSortableUser();

        $first = $user->sortableItems()->create(['group' => 'a', 'order' => 9, 'title' => 'First']);
        $second = $user->sortableItems()->create(['group' => 'a', 'order' => 8, 'title' => 'Second']);
        $third = $user->sortableItems()->create(['group' => 'b', 'order' => 7, 'title' => 'Third']);

        $this->visit("admin/sortable-users/{$user->id}/edit?view_mode=modal_table")
            ->submitForm('Submit', [
                'username' => $user->username,
                'email' => $user->email,
                'password' => 'secret',
                'sortableItems' => [
                    $second->id => [
                        'id' => $second->id,
                        'group' => 'a',
                        'title' => 'Second',
                        'order' => 99,
                        '_remove_' => 0,
                    ],
                    $first->id => [
                        'id' => $first->id,
                        'group' => 'a',
                        'title' => 'First',
                        'order' => 99,
                        '_remove_' => 0,
                    ],
                    $third->id => [
                        'id' => $third->id,
                        'group' => 'b',
                        'title' => 'Third',
                        '_remove_' => 0,
                    ],
                ],
            ]);

        $this->assertEquals(0, SortableItem::find($second->id)->order);
        $this->assertEquals(1, SortableItem::find($first->id)->order);
        $this->assertEquals(0, SortableItem::find($third->id)->order);
    }

    public function test_validation_errors_mark_modal_table_rows_without_opening_modal()
    {
        $user = $this->createSortableUser();

        $item = $user->sortableItems()->create(['group' => 'a', 'order' => 0, 'title' => 'First']);

        $this->visit("admin/sortable-users/{$user->id}/edit?view_mode=modal_table")
            ->submitForm('Submit', [
                'username' => $user->username,
                'email' => $user->email,
                'password' => 'secret',
                'sortableItems' => [
                    $item->id => [
                        'id' => $item->id,
                        'group' => 'a',
                        'title' => '',
                        'order' => 0,
                        '_remove_' => 0,
                    ],
                ],
            ]);

        $html = $this->response->getContent();

        $this->assertStringContainsString('has-many-modal-table-error', $html);
        $this->assertStringNotContainsString('show" role="dialog"', $html);
    }

    public function test_default_and_table_modes_do_not_render_modal_table_markup()
    {
        $this->visit('admin/sortable-users/create?view_mode=default');

        $defaultHtml = $this->response->getContent();

        $this->assertStringNotContainsString('has-many-modal-table', $defaultHtml);
        $this->assertStringNotContainsString('has-many-modal-apply', $defaultHtml);

        $this->visit('admin/sortable-users/create');

        $tableHtml = $this->response->getContent();

        $this->assertStringNotContainsString('has-many-modal-table', $tableHtml);
        $this->assertStringNotContainsString('has-many-modal-apply', $tableHtml);
    }

    public function test_apply_and_restore_translation_keys_exist_for_every_language()
    {
        foreach (glob(__DIR__.'/../resources/lang/*/admin.php') as $file) {
            $translations = require $file;

            $this->assertArrayHasKey('apply', $translations, $file);
            $this->assertArrayHasKey('restore', $translations, $file);
            $this->assertNotSame('', $translations['apply'], $file);
            $this->assertNotSame('', $translations['restore'], $file);
        }
    }

    protected function createSortableUser(): SortableUser
    {
        $user = new SortableUser;
        $user->username = 'sortable-user';
        $user->email = 'sortable@example.com';
        $user->password = 'secret';
        $user->save();

        return $user;
    }
}
