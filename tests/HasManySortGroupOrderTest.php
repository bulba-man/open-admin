<?php

use Illuminate\Support\ViewErrorBag;
use OpenAdmin\Admin\Admin;
use OpenAdmin\Admin\AdminServiceProvider;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Form\Field\HasMany;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Models\SortableUser;

class HasManySortGroupOrderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [AdminServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Admin::$script = [];
        $this->app['request']->setLaravelSession($this->app['session.store']);
        $this->app['view']->share('errors', new ViewErrorBag);
    }

    public static function modes(): array
    {
        return [['table'], ['modalTable']];
    }

    /** @return array{HasMany, Form} */
    protected function field(string $mode, array $rows = []): array
    {
        $form = new Form(new SortableUser);
        $field = $form->hasMany('sortableItems', function (Form\NestedForm $nested): void {
            $nested->text('group');
            $nested->text('title')->rules('required');
        })->mode($mode)->sortable()->sortColumn('position')->sortGroupColumn('group')->default($rows);

        return [$field, $form];
    }

    protected function html(HasMany $field): Crawler
    {
        return new Crawler((string) $field->render());
    }

    protected function titles(Crawler $html): array
    {
        return $html->filterXPath('//tbody/tr//input[contains(@name, "[title]")]')
            ->each(fn (Crawler $input): string => $input->attr('value'));
    }

    protected function configuration(Crawler $html): ?array
    {
        $json = $html->filter('table')->first()->attr('data-sort-group-order');

        return $json === null ? null : json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    #[DataProvider('modes')]
    public function test_explicit_groups_and_persisted_positions_are_independent(string $mode): void
    {
        [$field] = $this->field($mode, [
            ['id' => 1, 'group' => 10, 'position' => 0, 'title' => 'Ten'],
            ['id' => 2, 'group' => 20, 'position' => 8, 'title' => 'Twenty second'],
            ['id' => 3, 'group' => '20', 'position' => 1, 'title' => 'Twenty first'],
            ['id' => 4, 'group' => 30, 'position' => 0, 'title' => 'Thirty'],
        ]);
        $field->sortGroupOrder([20, 10, 30]);

        $html = $this->html($field);

        $this->assertSame(['Twenty first', 'Twenty second', 'Ten', 'Thirty'], $this->titles($html));
        $this->assertSame(['20', '10', '30', ''], $this->configuration($html)['groups']);
    }

    #[DataProvider('modes')]
    public function test_unknown_groups_follow_legacy_comparison_and_empty_groups_are_last(string $mode): void
    {
        [$field] = $this->field($mode, [
            ['id' => 1, 'group' => null, 'title' => 'Null'],
            ['id' => 2, 'group' => '10', 'title' => 'Ten'],
            ['id' => 3, 'group' => '2', 'title' => 'Two'],
            ['id' => 4, 'group' => '', 'title' => 'Empty'],
            ['id' => 5, 'group' => 0, 'title' => 'Zero'],
            ['id' => 6, 'group' => 'named', 'title' => 'Named'],
            ['id' => 7, 'title' => 'Missing'],
        ]);
        $field->sortGroupOrder(['named', 99, 'named', null, '']);

        $html = $this->html($field);

        $this->assertSame(['Named', 'Zero', 'Two', 'Ten', 'Null', 'Empty', 'Missing'], $this->titles($html));
        $this->assertSame(['named', '99'], $this->configuration($html)['priority']);
        $this->assertSame(7, $html->filter('tbody > tr')->count());
    }

    #[DataProvider('modes')]
    public function test_empty_priority_is_distinct_from_an_unconfigured_order(string $mode): void
    {
        $rows = [
            ['id' => 1, 'group' => 'b', 'title' => 'B'],
            ['id' => 2, 'group' => null, 'title' => 'Empty'],
            ['id' => 3, 'group' => 'a', 'title' => 'A'],
        ];
        [$legacy] = $this->field($mode, $rows);
        [$explicit] = $this->field($mode, $rows);
        $explicit->sortGroupOrder([]);

        $legacyHtml = $this->html($legacy);
        $explicitHtml = $this->html($explicit);

        $this->assertSame(['Empty', 'A', 'B'], $this->titles($legacyHtml));
        $this->assertNull($this->configuration($legacyHtml));
        $this->assertSame(['A', 'B', 'Empty'], $this->titles($explicitHtml));
        $this->assertSame([], $this->configuration($explicitHtml)['priority']);
    }

    #[DataProvider('modes')]
    public function test_ids_remain_exact_strings_and_equivalent_numeric_groups_do_not_interleave(string $mode): void
    {
        $groups = [9007199254740993, '9007199254740993', 9007199254740992, '02', '2', '02', 'constructor', '__proto__'];
        $rows = [];

        foreach ($groups as $index => $group) {
            $rows[] = ['id' => $index + 1, 'group' => $group, 'position' => $index, 'title' => (string) $index];
        }

        [$field] = $this->field($mode, $rows);
        $field->sortGroupOrder([9007199254740993, 'constructor', '__proto__']);
        $html = $this->html($field);
        $configuration = $this->configuration($html);

        $this->assertSame(['9007199254740993', 'constructor', '__proto__'], $configuration['priority']);
        $this->assertContains('9007199254740992', $configuration['groups'], true);
        $this->assertContains('02', $configuration['groups'], true);
        $this->assertContains('2', $configuration['groups'], true);
        $this->assertSame(['0', '1', '6', '7', '3', '5', '4', '2'], $this->titles($html));
    }

    #[DataProvider('modes')]
    public function test_closure_observes_parent_data_once_per_render_and_is_not_called_on_prepare(string $mode): void
    {
        [$field, $form] = $this->field($mode, [
            ['id' => 1, 'group' => 10, 'title' => 'Ten'],
            ['id' => 2, 'group' => 20, 'title' => 'Twenty'],
        ]);
        $calls = 0;
        $field->sortGroupOrder(function (Form $parent) use (&$calls, $form): array {
            $this->assertSame($form, $parent);
            $calls++;

            return $parent->model()->getAttribute('group_order');
        });
        $this->assertSame(0, $calls);
        $field->getValidator(['sortableItems' => [['title' => 'Valid', '_remove_' => 0]]]);
        $field->prepare([['group' => 20, 'title' => 'Valid', '_remove_' => 0]]);
        $this->assertSame(0, $calls);

        $form->model()->forceFill(['group_order' => [20, 10]]);
        $this->assertSame(['Twenty', 'Ten'], $this->titles($this->html($field)));
        $this->assertSame(1, $calls);

        $form->model()->forceFill(['group_order' => [10, 20]]);
        $this->assertSame(['Ten', 'Twenty'], $this->titles($this->html($field)));
        $this->assertSame(2, $calls);
    }

    public function test_invalid_closure_result_is_reported(): void
    {
        [$field] = $this->field('table');
        $field->sortGroupOrder(fn (): string => 'invalid');

        $this->expectException(InvalidArgumentException::class);
        $this->html($field);
    }

    public function test_invalid_group_id_is_reported(): void
    {
        [$field] = $this->field('table');
        $field->sortGroupOrder([new stdClass]);

        $this->expectException(InvalidArgumentException::class);
        $this->html($field);
    }

    public static function inactiveModes(): array
    {
        return [['default', true, 'group'], ['tab', true, 'group'], ['table', false, 'group'], ['modalTable', false, 'group'], ['table', true, null]];
    }

    #[DataProvider('inactiveModes')]
    public function test_inactive_configuration_never_calls_closure(string $mode, bool $sortable, ?string $groupColumn): void
    {
        [$field] = $this->field($mode);
        $field->sortable($sortable)->sortGroupColumn($groupColumn)->sortGroupOrder(function (): array {
            $this->fail('Inactive configuration must not evaluate sortGroupOrder.');
        });

        $this->assertStringNotContainsString('data-sort-group-order', (string) $field->render());
    }

    public function test_standalone_table_keeps_its_existing_behavior(): void
    {
        $form = new Form(new SortableUser);
        $field = $form->table('data', function (Form\NestedForm $nested): void {
            $nested->text('group');
        })->sortable()->sortGroupColumn('group')->sortGroupOrder(function (): array {
            $this->fail('Standalone Table must not evaluate sortGroupOrder.');
        });

        $this->assertStringNotContainsString('data-sort-group-order', (string) $field->render());
    }

    #[DataProvider('modes')]
    public function test_prepare_numbers_active_rows_independently_without_changing_groups(string $mode): void
    {
        [$field] = $this->field($mode);
        $field->sortGroupOrder([20, 10]);
        $input = [
            'new_8' => ['group' => 20, 'position' => 99, 'title' => 'First', '_remove_' => 0],
            31 => ['id' => 31, 'group' => 20, 'position' => 25, 'title' => 'Deleted', '_remove_' => 1],
            42 => ['id' => 42, 'group' => 10, 'position' => 99, 'title' => 'Other', '_remove_' => 0],
            'new_9' => ['group' => '20', 'position' => 99, 'title' => 'Second', '_remove_' => 0],
        ];

        $prepared = $field->prepare($input);

        $this->assertSame(array_keys($input), array_keys($prepared));
        $this->assertSame(0, $prepared['new_8']['position']);
        $this->assertSame(0, $prepared[42]['position']);
        $this->assertSame(1, $prepared['new_9']['position']);
        $this->assertSame('20', $prepared['new_9']['group']);
        $this->assertSame($input[31], $prepared[31]);
    }

    #[DataProvider('modes')]
    public function test_old_input_regroups_stably_and_preserves_keys_values_and_deletions(string $mode): void
    {
        $input = [
            '__modal_table_present__' => '1',
            'new_7' => ['group' => '10', 'title' => 'Edited new', 'position' => 99, '_remove_' => 0],
            81 => ['id' => 81, 'group' => '20', 'title' => 'Twenty first', 'position' => 99, '_remove_' => 0],
            93 => ['id' => 93, 'group' => '10', 'title' => 'Deleted', 'position' => 3, '_remove_' => 1],
            'new_2' => ['group' => '20', 'title' => 'Twenty second', 'position' => 0, '_remove_' => 0],
        ];
        $this->app['session.store']->flashInput(['sortableItems' => $input]);
        [$field] = $this->field($mode, [['id' => 1, 'group' => '20', 'title' => 'Original']]);
        $field->sortGroupOrder([20, 10]);

        $html = $this->html($field);
        $rows = $html->filter('tbody > tr');

        $this->assertSame(['Twenty first', 'Twenty second', 'Edited new', 'Deleted'], $this->titles($html));
        $this->assertSame(['81', 'new_2', 'new_7', '93'], $rows->each(fn (Crawler $row): string => $row->attr('data-has-many-row-key')));
        $this->assertSame(1, $rows->filter('input[name="sortableItems[new_7][title]"]')->count());
        $this->assertSame(1, $rows->filter('input[name="sortableItems[new_2][title]"]')->count());
        $deleted = $rows->last();
        $this->assertSame('1', $deleted->filter('input[name="sortableItems[93][_remove_]"]')->attr('value'));
        $this->assertSame('3', $deleted->filter('input[name="sortableItems[93][position]"]')->attr('value'));

        if ($mode === 'table') {
            $this->assertStringContainsString('display: none', $deleted->attr('style'));
        } else {
            $this->assertStringContainsString('has-many-modal-table-deleted', $deleted->attr('class'));
            $this->assertStringContainsString('d-none', $deleted->filter('.has-many-modal-edit')->attr('class'));
            $this->assertStringNotContainsString('d-none', $deleted->filter('.has-many-modal-restore')->attr('class'));
        }

        $this->assertSame($input, $this->app['session.store']->getOldInput('sortableItems'));
        $prepared = $field->prepare($input);
        $this->assertSame($input[93], $prepared[93]);
        $this->assertSame(0, $prepared[81]['position']);
        $this->assertSame(1, $prepared['new_2']['position']);
    }

    #[DataProvider('modes')]
    public function test_explicit_empty_old_input_does_not_restore_default_rows(string $mode): void
    {
        $this->app['session.store']->flashInput(['sortableItems' => []]);
        [$field] = $this->field($mode, [['id' => 1, 'group' => 20, 'title' => 'Original']]);
        $field->sortGroupOrder([20]);

        $this->assertSame([], $this->titles($this->html($field)));
    }

    public function test_marker_only_old_input_renders_no_rows(): void
    {
        $this->app['session.store']->flashInput(['sortableItems' => ['__modal_table_present__' => '1']]);
        [$field] = $this->field('modalTable', [['id' => 1, 'group' => 20, 'title' => 'Original']]);
        $field->sortGroupOrder([20]);

        $html = $this->html($field);

        $this->assertSame([], $this->titles($html));
        $this->assertSame('1', $html->filter('input[name="sortableItems[__modal_table_present__]"]')->attr('value'));
    }

    public function test_modal_marker_is_filtered_before_validation_even_without_new_configuration(): void
    {
        [$field] = $this->field('modalTable');
        $marker = ['__modal_table_present__' => '1'];

        $this->assertFalse($field->getValidator(['sortableItems' => $marker]));
        $this->assertSame([], $field->prepare($marker));

        $valid = $marker + ['new_1' => ['group' => '20', 'title' => 'Valid', '_remove_' => 0]];
        $this->assertTrue($field->getValidator(['sortableItems' => $valid])->passes());

        $invalid = $marker + [
            'new_1' => ['group' => '20', 'title' => '', '_remove_' => 0],
            'new_2' => ['group' => '10', 'title' => '', '_remove_' => 1],
        ];
        $validator = $field->getValidator(['sortableItems' => $invalid]);
        $this->assertFalse($validator->passes());
        $this->assertSame(['sortableItems.new_1.title'], $validator->errors()->keys());
        $this->assertSame(1, $invalid['new_2']['_remove_']);

        $html = $this->html($field);
        $this->assertSame('1', $html->filter('input[name="sortableItems[__modal_table_present__]"]')->attr('value'));
        $this->assertNull($this->configuration($html));
    }

    #[DataProvider('modes')]
    public function test_json_configuration_preserves_html_sensitive_and_mixed_identifiers(string $mode): void
    {
        $special = 'named"<&\' /🙂';
        $groups = ['2', '10', '10a', $special];
        $rows = [];

        foreach ($groups as $index => $group) {
            $rows[] = ['id' => $index + 1, 'group' => $group, 'title' => $group];
        }

        [$field] = $this->field($mode, $rows);
        $field->sortGroupOrder([$special]);
        $html = $this->html($field);
        $configuration = $this->configuration($html);

        $this->assertSame([$special], $configuration['priority']);
        $this->assertSame([...$this->titles($html), ''], $configuration['groups']);
        $this->assertSame(1, $html->filter('table[data-sort-group-order]')->count());
    }

    #[DataProvider('modes')]
    public function test_loaded_values_and_equal_positions_keep_their_stable_order(string $mode): void
    {
        [$field] = $this->field($mode);
        $field->fill(['sortableItems' => [
            ['id' => 1, 'group' => 10, 'position' => 0, 'title' => 'Ten'],
            ['id' => 2, 'group' => 20, 'position' => 1, 'title' => 'Twenty first'],
            ['id' => 3, 'group' => 20, 'position' => 1, 'title' => 'Twenty second'],
            ['id' => 4, 'group' => 20, 'title' => 'Unpositioned'],
        ]]);
        $field->sortGroupOrder([20, 10]);

        $this->assertSame(['Twenty first', 'Twenty second', 'Unpositioned', 'Ten'], $this->titles($this->html($field)));
    }
}
