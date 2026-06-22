<?php

namespace AnourValar\EloquentRequest\Tests;

use AnourValar\EloquentRequest\Tests\Models\User;
use AnourValar\EloquentRequest\Tests\Support\BareController;
use AnourValar\EloquentRequest\Tests\Support\MethodController;
use AnourValar\EloquentRequest\Tests\Support\PropertyController;

class ControllerTraitTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function test_explicit_profile_and_request(): void
    {
        $controller = new PropertyController();

        $result = $controller->run(
            User::class,
            ['filter' => ['id' => ['=']]],
            ['filter' => ['id' => ['=' => 3]]]
        );

        $this->assertSame(['Charlie'], $result->pluck('name')->all());
    }

    /**
     * @return void
     */
    public function test_profile_resolved_from_property(): void
    {
        $controller = new PropertyController();

        $result = $controller->run(User::class, null, ['filter' => ['status' => ['=' => 'active']], 'sort' => ['id' => 'ASC']]);

        $this->assertSame(['Alice', 'Bob'], $result->pluck('name')->all());
    }

    /**
     * @return void
     */
    public function test_profile_resolved_from_method(): void
    {
        $controller = new MethodController();

        request()->merge(['filter' => ['status' => ['=' => 'blocked']], 'sort' => ['id' => 'ASC']]);

        $result = $controller->run(User::class);

        $this->assertSame(['Charlie', 'Dave'], $result->pluck('name')->all());
    }

    /**
     * @return void
     */
    public function test_request_resolved_from_request_helper(): void
    {
        $controller = new PropertyController();

        request()->merge(['filter' => ['status' => ['=' => 'active']], 'sort' => ['id' => 'ASC']]);

        $result = $controller->run(User::class);

        $this->assertSame(['Alice', 'Bob'], $result->pluck('name')->all());
    }

    /**
     * @return void
     */
    public function test_get_build_request(): void
    {
        $controller = new PropertyController();
        $controller->run(User::class, null, ['filter' => ['status' => ['=' => 'active']], 'per_page' => 9]);

        $this->assertInstanceOf(\AnourValar\EloquentRequest\Helpers\Request::class, $controller->last());
        $this->assertSame(9, $controller->last()->get('per_page'));
    }

    /**
     * @return void
     */
    public function test_get_build_request_without_build_throws(): void
    {
        $controller = new PropertyController();
        $controller->run(User::class, null, []);
        $controller->resetLast();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Incorrect usage.');
        $controller->last();
    }

    /**
     * @return void
     */
    public function test_missing_profile_throws(): void
    {
        $controller = new BareController();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Profile cannot be null.');
        $controller->run(User::class);
    }
}
