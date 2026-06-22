<?php

namespace AnourValar\EloquentRequest\Tests\Service;

use AnourValar\EloquentRequest\Actions\CursorAction;
use AnourValar\EloquentRequest\Actions\GeneratorAction;
use AnourValar\EloquentRequest\Actions\GetAction;
use AnourValar\EloquentRequest\Actions\NullAction;
use AnourValar\EloquentRequest\Actions\PaginateAction;
use AnourValar\EloquentRequest\Tests\AbstractSuite;
use AnourValar\EloquentRequest\Tests\Models\User;

class ActionTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function test_default_is_paginate(): void
    {
        $result = $this->build(User::class, [], []);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $result);
        $this->assertSame(4, $result->total());
        $this->assertSame(20, $result->perPage());
        $this->assertSame(1, $result->currentPage());
    }

    /**
     * @return void
     */
    public function test_paginate_with_page_and_per_page(): void
    {
        $result = $this->build(User::class, ['sort' => ['id']], ['per_page' => 2, 'page' => 2, 'sort' => ['id' => 'ASC']]);

        $this->assertSame(2, $result->perPage());
        $this->assertSame(2, $result->currentPage());
        $this->assertSame(['Charlie', 'Dave'], $result->pluck('name')->all());
    }

    /**
     * @return void
     */
    public function test_simple_paginate(): void
    {
        $result = $this->build(User::class, ['options' => [PaginateAction::OPTION_SIMPLE_PAGINATE]], []);

        $this->assertInstanceOf(\Illuminate\Pagination\Paginator::class, $result);
    }

    /**
     * @return void
     */
    public function test_per_page_must_be_natural(): void
    {
        $this->expectExceptionMessage('The number of entries per page must be a natural number.');

        $this->build(User::class, [], ['per_page' => 0]);
    }

    /**
     * @return void
     */
    public function test_per_page_over_max(): void
    {
        $this->expectExceptionMessage('Maximum number of entries per page is 2000.');

        $this->build(User::class, [], ['per_page' => 2001]);
    }

    /**
     * @return void
     */
    public function test_page_must_be_natural(): void
    {
        $this->expectExceptionMessage('The page number must be a natural number.');

        $this->build(User::class, [], ['page' => 0]);
    }

    /**
     * @return void
     */
    public function test_page_over_max(): void
    {
        $this->expectExceptionMessage('Maximum available page is 5.');

        $this->build(User::class, ['options' => [PaginateAction::OPTION_PAGE_MAX => 5]], ['page' => 6]);
    }

    /**
     * @return void
     */
    public function test_page_over_last_is_forbidden(): void
    {
        $this->expectExceptionMessage('The Page does not exist.');

        $this->build(User::class, ['options' => [PaginateAction::OPTION_PAGE_OVER_LAST_IS_FORBIDDEN]], ['page' => 5, 'per_page' => 2]);
    }

    /**
     * @return void
     */
    public function test_get_action(): void
    {
        $result = $this->build(User::class, ['options' => [GetAction::OPTION_APPLY]], []);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
        $this->assertCount(4, $result);
    }

    /**
     * @return void
     */
    public function test_get_action_with_limit(): void
    {
        $result = $this->build(
            User::class,
            ['options' => [GetAction::OPTION_APPLY, GetAction::OPTION_LIMIT => 2], 'sort' => ['id']],
            ['sort' => ['id' => 'ASC']]
        );

        $this->assertSame(['Alice', 'Bob'], $result->pluck('name')->all());
    }

    /**
     * @return void
     */
    public function test_null_action_returns_builder(): void
    {
        $result = $this->build(User::class, ['options' => [NullAction::OPTION_APPLY]], []);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
        $this->assertCount(4, $result->get());
    }

    /**
     * @return void
     */
    public function test_cursor_action(): void
    {
        $result = $this->build(User::class, ['options' => [CursorAction::OPTION_APPLY]], []);

        $this->assertInstanceOf(\Illuminate\Support\LazyCollection::class, $result);
        $this->assertCount(4, $result);
    }

    /**
     * @return void
     */
    public function test_cursor_action_with_limit(): void
    {
        $result = $this->build(
            User::class,
            ['options' => [CursorAction::OPTION_APPLY, CursorAction::OPTION_LIMIT => 2]],
            []
        );

        $this->assertCount(2, $result);
    }

    /**
     * @return void
     */
    public function test_cursor_paginate(): void
    {
        $result = $this->build(
            User::class,
            ['options' => [\AnourValar\EloquentRequest\Actions\CursorPaginateAction::OPTION_APPLY], 'sort' => ['id']],
            ['per_page' => 2, 'sort' => ['id' => 'ASC']]
        );

        $this->assertInstanceOf(\Illuminate\Pagination\CursorPaginator::class, $result);
        $this->assertSame(['Alice', 'Bob'], $result->pluck('name')->all());
        $this->assertTrue($result->hasMorePages());
    }

    /**
     * @return void
     */
    public function test_cursor_paginate_incorrect_cursor(): void
    {
        $this->expectExceptionMessage('Incorrect page cursor.');

        $this->build(
            User::class,
            ['options' => [\AnourValar\EloquentRequest\Actions\CursorPaginateAction::OPTION_APPLY]],
            ['cursor' => 'broken-cursor']
        );
    }

    /**
     * @return void
     */
    public function test_cursor_paginate_per_page_must_be_natural(): void
    {
        $this->expectExceptionMessage('The number of entries per page must be a natural number.');

        $this->build(
            User::class,
            ['options' => [\AnourValar\EloquentRequest\Actions\CursorPaginateAction::OPTION_APPLY]],
            ['per_page' => 0]
        );
    }

    /**
     * @return void
     */
    public function test_cursor_paginate_per_page_over_max(): void
    {
        $this->expectExceptionMessage('Maximum number of entries per page is 2000.');

        $this->build(
            User::class,
            ['options' => [\AnourValar\EloquentRequest\Actions\CursorPaginateAction::OPTION_APPLY]],
            ['per_page' => 2001]
        );
    }

    /**
     * @return void
     */
    public function test_generator_action(): void
    {
        $generator = $this->build(
            User::class,
            ['options' => [GeneratorAction::OPTION_APPLY_CHUNK => 2], 'sort' => ['id']],
            ['sort' => ['id' => 'ASC']]
        );

        $this->assertInstanceOf(\Closure::class, $generator);

        $names = [];
        foreach ($generator() as $user) {
            $names[] = $user->name;
        }

        $this->assertSame(['Alice', 'Bob', 'Charlie', 'Dave'], $names);
    }

    /**
     * @return void
     */
    public function test_generator_action_with_limit(): void
    {
        $generator = $this->build(
            User::class,
            ['options' => [GeneratorAction::OPTION_APPLY_CHUNK => 2, GeneratorAction::OPTION_LIMIT => 3], 'sort' => ['id']],
            ['sort' => ['id' => 'ASC']]
        );

        $names = [];
        foreach ($generator() as $user) {
            $names[] = $user->name;
        }

        $this->assertSame(['Alice', 'Bob', 'Charlie'], $names);
    }

    /**
     * @return void
     */
    public function test_generator_action_by_id(): void
    {
        $generator = $this->build(
            User::class,
            [
                'options' => [
                    GeneratorAction::OPTION_APPLY_CHUNK => 2,
                    GeneratorAction::OPTION_APPLY_CHUNK_ORDER_BY => ['users.id' => 'asc'],
                ],
            ],
            []
        );

        $names = [];
        foreach ($generator() as $user) {
            $names[] = $user->name;
        }

        $this->assertSame(['Alice', 'Bob', 'Charlie', 'Dave'], $names);
    }

    /**
     * @return void
     */
    public function test_generator_action_by_id_desc(): void
    {
        $generator = $this->build(
            User::class,
            [
                'options' => [
                    GeneratorAction::OPTION_APPLY_CHUNK => 2,
                    GeneratorAction::OPTION_APPLY_CHUNK_ORDER_BY => ['users.id' => 'desc'],
                ],
            ],
            []
        );

        $names = [];
        foreach ($generator() as $user) {
            $names[] = $user->name;
        }

        $this->assertSame(['Dave', 'Charlie', 'Bob', 'Alice'], $names);
    }
}
