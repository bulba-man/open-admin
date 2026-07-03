<?php

use OpenAdmin\Admin\Auth\Database\Administrator;
use Tests\Models\SortableItem;
use Tests\Models\SortableUser;

class SortableHasManyTableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(Administrator::first(), 'admin');
    }

    public function test_sortable_has_many_table_renders_drag_controls_and_hidden_order_input()
    {
        $this->visit('admin/sortable-users/create');

        $html = $this->response->getContent();

        $this->assertStringContainsString('data-sort-column="order"', $html);
        $this->assertStringContainsString('data-sort-group-column="group"', $html);
        $this->assertStringContainsString('data-sort-with="drag"', $html);
        $this->assertStringContainsString('icon-arrows-alt-v btn btn-light handle', $html);
        $this->assertStringContainsString('name="sortableItems[new___key__][order]"', $html);
        $this->assertSame(1, substr_count($html, 'name="sortableItems[new___key__][order]"'));
    }

    public function test_sortable_false_disables_has_many_table_controls_and_injected_order_input()
    {
        $this->visit('admin/sortable-users/create?sortable=false');

        $html = $this->response->getContent();

        $this->assertStringNotContainsString('data-sort-column="order"', $html);
        $this->assertStringNotContainsString('icon-arrows-alt-v btn btn-light handle', $html);
        $this->assertStringNotContainsString('name="sortableItems[new___key__][order]"', $html);
    }

    public function test_sort_with_buttons_and_all_render_expected_controls()
    {
        $this->visit('admin/sortable-users/create?sort_with=buttons');

        $buttonsHtml = $this->response->getContent();

        $this->assertStringContainsString('has-many-sort-up', $buttonsHtml);
        $this->assertStringContainsString('has-many-sort-down', $buttonsHtml);
        $this->assertStringNotContainsString('icon-arrows-alt-v btn btn-light handle', $buttonsHtml);

        $this->visit('admin/sortable-users/create?sort_with=all');

        $allHtml = $this->response->getContent();

        $this->assertStringContainsString('has-many-sort-up', $allHtml);
        $this->assertStringContainsString('has-many-sort-down', $allHtml);
        $this->assertStringContainsString('icon-arrows-alt-v btn btn-light handle', $allHtml);
    }

    public function test_custom_sort_column_uses_configured_submitted_column()
    {
        $this->visit('admin/sortable-users/create?sort_column=position');

        $html = $this->response->getContent();

        $this->assertStringContainsString('data-sort-column="position"', $html);
        $this->assertStringContainsString('name="sortableItems[new___key__][position]"', $html);
        $this->assertStringNotContainsString('name="sortableItems[new___key__][order]"', $html);
    }

    public function test_submitted_order_is_normalized_by_group_from_visible_row_order()
    {
        $user = $this->createSortableUser();

        $first = $user->sortableItems()->create(['group' => 'a', 'order' => 9, 'title' => 'First']);
        $second = $user->sortableItems()->create(['group' => 'a', 'order' => 8, 'title' => 'Second']);
        $third = $user->sortableItems()->create(['group' => 'b', 'order' => 7, 'title' => 'Third']);

        $this->visit("admin/sortable-users/{$user->id}/edit")
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

    public function test_persisted_rows_render_in_group_and_order_sequence()
    {
        $user = $this->createSortableUser();

        $user->sortableItems()->create(['group' => 'b', 'order' => 0, 'title' => 'Third']);
        $user->sortableItems()->create(['group' => 'a', 'order' => 1, 'title' => 'Second']);
        $user->sortableItems()->create(['group' => 'a', 'order' => 0, 'title' => 'First']);

        $this->visit("admin/sortable-users/{$user->id}/edit");

        $html = $this->response->getContent();

        $this->assertTrue(strpos($html, 'value="First"') < strpos($html, 'value="Second"'));
        $this->assertTrue(strpos($html, 'value="Second"') < strpos($html, 'value="Third"'));
    }

    public function test_default_has_many_mode_does_not_receive_sortable_table_side_effects()
    {
        $this->visit('admin/sortable-users/create?view_mode=default');

        $html = $this->response->getContent();

        $this->assertStringNotContainsString('data-sort-column="order"', $html);
        $this->assertStringNotContainsString('name="sortableItems[new___key__][order]"', $html);
    }

    public function test_standalone_table_does_not_receive_has_many_sortable_side_effects()
    {
        $this->visit('admin/sortable-users/create?standalone_table=1');

        $html = $this->response->getContent();

        $this->assertStringNotContainsString('data-sort-column="position"', $html);
        $this->assertStringNotContainsString('data-sort-group-column="group"', $html);
        $this->assertStringNotContainsString('name="data[new___key__][position]"', $html);
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
