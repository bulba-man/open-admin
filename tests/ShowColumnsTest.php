<?php

use OpenAdmin\Admin\AdminServiceProvider;
use OpenAdmin\Admin\Show;
use Orchestra\Testbench\TestCase;
use Tests\Models\User;

class ShowColumnsTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            AdminServiceProvider::class,
        ];
    }

    public function test_show_fields_can_be_rendered_in_columns(): void
    {
        $user = new User;
        $user->forceFill([
            'id' => 1,
            'username' => 'John Doe',
            'email' => 'john@example.com',
            'mobile' => '123456789',
        ]);

        $show = new Show($user, function (Show $show) {
            $show->field('id', 'ID');

            $show->columns('Contact')->add(6, function (Show $show) {
                $show->field('username', 'Username');
                $show->field('email', 'Email');
            })->add([6, 0], function (Show $show) {
                $show->field('mobile', 'Mobile');
            });
        });

        $html = $show->render();

        $this->assertStringContainsString('divider-text', $html);
        $this->assertStringContainsString('Contact', $html);
        $this->assertStringContainsString('col-sm-6', $html);
        $this->assertSame(1, substr_count($html, 'John Doe'));
        $this->assertSame(1, substr_count($html, 'john@example.com'));
        $this->assertSame(1, substr_count($html, '123456789'));
    }
}
