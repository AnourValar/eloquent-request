<?php

namespace AnourValar\EloquentRequest\Tests\Service;

use AnourValar\EloquentRequest\Actions\GetAction;
use AnourValar\EloquentRequest\Service;
use AnourValar\EloquentRequest\Tests\AbstractSuite;
use AnourValar\EloquentRequest\Tests\Models\User;
use AnourValar\EloquentRequest\Tests\Support\EchoAction;
use AnourValar\EloquentRequest\Tests\Support\MarkerBuilder;

class ConfigTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function test_extend_actions_prepends_a_custom_action(): void
    {
        $result = (new Service())
            ->extendActions('echo', new EchoAction())
            ->buildBy(User::class, ['options' => [EchoAction::OPTION_APPLY]], []);

        $this->assertSame('echo-action-result', $result);
    }

    /**
     * @return void
     */
    public function test_extend_actions_can_remove_an_action(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No actions are available for the request.');

        (new Service())
            ->extendActions('paginate', null)
            ->buildBy(User::class, [], []);
    }

    /**
     * @return void
     */
    public function test_extend_builders_adds_a_custom_builder(): void
    {
        $buildRequest = null;

        $result = (new Service())
            ->extendBuilders('marker', new MarkerBuilder())
            ->buildBy(User::class, ['options' => [GetAction::OPTION_APPLY], 'sort' => ['id']], ['sort' => ['id' => 'ASC']], $buildRequest);

        $this->assertSame(['Alice', 'Bob'], $result->pluck('name')->all());
        $this->assertTrue($buildRequest->get('marker'));
    }

    /**
     * @return void
     */
    public function test_replace_config_changes_per_page_key(): void
    {
        $result = (new Service())
            ->replaceConfig(['per_page_key' => 'limit'])
            ->buildBy(User::class, [], ['limit' => 2]);

        $this->assertSame(2, $result->perPage());
    }
}
