<?php

namespace AnourValar\EloquentRequest\Tests\Service;

use AnourValar\EloquentRequest\Tests\AbstractSuite;
use AnourValar\EloquentRequest\Tests\Models\User;

class SortTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function test_sort_asc_desc(): void
    {
        $profile = ['sort' => ['id', 'name']];

        $this->assertSame(
            ['Alice', 'Bob', 'Charlie', 'Dave'],
            $this->build(User::class, $profile, ['sort' => ['name' => 'ASC']])->pluck('name')->all()
        );

        $this->assertSame(
            ['Dave', 'Charlie', 'Bob', 'Alice'],
            $this->build(User::class, $profile, ['sort' => ['name' => 'DESC']])->pluck('name')->all()
        );
    }

    /**
     * @return void
     */
    public function test_sort_direction_is_case_insensitive(): void
    {
        $profile = ['sort' => ['name']];

        $this->assertSame(
            ['Dave', 'Charlie', 'Bob', 'Alice'],
            $this->build(User::class, $profile, ['sort' => ['name' => 'desc']])->pluck('name')->all()
        );
    }

    /**
     * @return void
     */
    public function test_multiple_sorts(): void
    {
        $profile = ['sort' => ['status', 'id']];

        $this->assertSame(
            ['Alice', 'Bob', 'Charlie', 'Dave'],
            $this->build(User::class, $profile, ['sort' => ['status' => 'ASC', 'id' => 'ASC']])->pluck('name')->all()
        );
    }

    /**
     * @return void
     */
    public function test_sort_via_alias(): void
    {
        $profile = ['sort' => ['name_len'], 'alias' => ['name_len' => 'length(name)']];

        $this->assertSame(
            ['Bob', 'Dave', 'Alice', 'Charlie'],
            $this->build(User::class, $profile, ['sort' => ['name_len' => 'ASC']])->pluck('name')->all()
        );
    }

    /**
     * @return void
     */
    public function test_sort_not_supported(): void
    {
        $this->expectExceptionMessage('Sorting for the email is not available.');

        $this->build(User::class, ['sort' => ['name']], ['sort' => ['email' => 'ASC']]);
    }

    /**
     * @return void
     */
    public function test_sort_invalid_direction(): void
    {
        $this->expectExceptionMessage('Sorting for the User Name is incorrect.');

        $this->build(User::class, ['sort' => ['name']], ['sort' => ['name' => 'UP']]);
    }

    /**
     * @return void
     */
    public function test_numeric_sort_key_is_ignored(): void
    {
        // numeric keys are silently skipped (no error)
        $result = $this->build(User::class, ['sort' => ['name']], ['sort' => ['ASC']]);

        $this->assertCount(4, $result);
    }
}
