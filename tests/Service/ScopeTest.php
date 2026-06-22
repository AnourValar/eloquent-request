<?php

namespace AnourValar\EloquentRequest\Tests\Service;

use AnourValar\EloquentRequest\Tests\AbstractSuite;
use AnourValar\EloquentRequest\Tests\Models\User;

class ScopeTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function test_eloquent_scope(): void
    {
        $profile = ['scope' => ['active'], 'sort' => ['id']];

        $this->assertSame(
            ['Alice', 'Bob'],
            $this->build(User::class, $profile, ['scope' => ['active' => true], 'sort' => ['id' => 'ASC']])->pluck('name')->all()
        );

        $this->assertSame(
            ['Charlie', 'Dave'],
            $this->build(User::class, $profile, ['scope' => ['active' => false], 'sort' => ['id' => 'ASC']])->pluck('name')->all()
        );
    }

    /**
     * @return void
     */
    public function test_eloquent_scope_with_argument(): void
    {
        $profile = ['scope' => ['olderThan'], 'sort' => ['id']];

        $this->assertSame(
            ['Charlie'],
            $this->build(User::class, $profile, ['scope' => ['olderThan' => 35]])->pluck('name')->all()
        );
    }

    /**
     * @return void
     */
    public function test_closure_scope(): void
    {
        $profile = [
            'scope' => [
                'minAge' => function ($query, $value) {
                    $query->where('age', '>', $value);
                },
            ],
            'sort' => ['id'],
        ];

        $this->assertSame(
            ['Alice', 'Charlie'],
            $this->build(User::class, $profile, ['scope' => ['minAge' => 25], 'sort' => ['id' => 'ASC']])->pluck('name')->all()
        );
    }

    /**
     * @return void
     */
    public function test_scope_validation_is_translated_into_error(): void
    {
        $profile = ['scope' => ['olderThan']];

        try {
            $this->build(User::class, $profile, ['scope' => ['olderThan' => 'not-a-number']]);
            $this->fail('ValidationException was not thrown');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('scope.olderThan.age', $e->errors());
        }
    }

    /**
     * @return void
     */
    public function test_scope_not_supported(): void
    {
        $this->expectExceptionMessage('Filter for scope unknown not available.');

        $this->build(User::class, ['scope' => ['active']], ['scope' => ['unknown' => 1]]);
    }
}
