<?php

namespace AnourValar\EloquentRequest\Tests\Service;

use AnourValar\EloquentRequest\Helpers\Request;
use AnourValar\EloquentRequest\Tests\AbstractSuite;
use AnourValar\EloquentRequest\Tests\Models\User;

class HandlerTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function test_handler_can_wrap_the_action(): void
    {
        $captured = null;

        $result = $this->build(
            User::class,
            [],
            [],
            $buildRequest,
            function ($runAction, $buildRequest) use (&$captured) {
                $captured = $buildRequest;

                return 'wrapped:' . $runAction()->total();
            }
        );

        $this->assertSame('wrapped:4', $result);
        $this->assertInstanceOf(Request::class, $captured);
    }

    /**
     * @return void
     */
    public function test_handler_receives_build_request_with_applied_params(): void
    {
        $this->build(
            User::class,
            ['filter' => ['status' => ['=']]],
            ['filter' => ['status' => ['=' => 'active']], 'per_page' => 5],
            $buildRequest,
            function ($runAction, Request $buildRequest) {
                return $buildRequest;
            }
        );

        $this->assertSame('active', $buildRequest->filter('status.='));
        $this->assertSame(5, $buildRequest->get('per_page'));
    }

    /**
     * @return void
     */
    public function test_non_model_argument_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('First argument must to be Eloquent model.');

        $this->build(new \stdClass(), [], []);
    }

    /**
     * @return void
     */
    public function test_model_instance_is_resolved(): void
    {
        $result = $this->build(new User(), [], []);

        $this->assertSame(4, $result->total());
    }

    /**
     * @return void
     */
    public function test_builder_instance_is_resolved(): void
    {
        $result = $this->build(User::where('status', 'active'), [], []);

        $this->assertSame(2, $result->total());
    }
}
