<?php

namespace AnourValar\EloquentRequest\Tests\Service;

use AnourValar\EloquentRequest\Tests\AbstractSuite;
use AnourValar\EloquentRequest\Tests\Models\User;

class ValidationTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function test_filter_not_supported(): void
    {
        try {
            $this->build(User::class, ['filter' => ['name' => ['=']]], ['filter' => ['secret' => ['=' => 1]]]);
            $this->fail('ValidationException was not thrown');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('filter.secret.=', $e->errors());
            $this->assertSame('Filter for secret is not available.', $e->errors()['filter.secret.='][0]);
        }
    }

    /**
     * @return void
     */
    public function test_operation_not_supported(): void
    {
        try {
            $this->build(User::class, ['filter' => ['name' => ['=']]], ['filter' => ['name' => ['like' => 'abc']]]);
            $this->fail('ValidationException was not thrown');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('filter.name.like', $e->errors());
            $this->assertSame('Filter for User Name does not support the specified operation.', $e->errors()['filter.name.like'][0]);
        }
    }

    /**
     * @return void
     */
    public function test_operation_not_exists(): void
    {
        try {
            $this->build(User::class, ['filter' => ['name' => ['weird']]], ['filter' => ['name' => ['weird' => 'abc']]]);
            $this->fail('ValidationException was not thrown');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('filter.name.weird', $e->errors());
            $this->assertSame('Filter for User Name contains a non-existent operation.', $e->errors()['filter.name.weird'][0]);
        }
    }

    /**
     * @return void
     */
    public function test_ranges_min(): void
    {
        $profile = ['filter' => ['age' => ['=']], 'ranges' => ['age' => ['min' => 18, 'max' => 65]]];

        $this->expectExceptionMessage('Filter for User Age below range.');
        $this->build(User::class, $profile, ['filter' => ['age' => ['=' => 10]]]);
    }

    /**
     * @return void
     */
    public function test_ranges_max(): void
    {
        $profile = ['filter' => ['age' => ['=']], 'ranges' => ['age' => ['min' => 18, 'max' => 65]]];

        $this->expectExceptionMessage('Filter for User Age above range.');
        $this->build(User::class, $profile, ['filter' => ['age' => ['=' => 70]]]);
    }

    /**
     * @return void
     */
    public function test_ranges_passes_inside_bounds(): void
    {
        $profile = ['filter' => ['age' => ['=']], 'ranges' => ['age' => ['min' => 18, 'max' => 65]]];

        $result = $this->build(User::class, $profile, ['filter' => ['age' => ['=' => 30]]]);
        $this->assertSame(['Alice'], $result->pluck('name')->all());
    }

    /**
     * @return void
     */
    public function test_ranges_validates_each_item_of_a_list(): void
    {
        $profile = ['filter' => ['age' => ['in']], 'ranges' => ['age' => ['min' => 18]]];

        $this->expectExceptionMessage('Filter for User Age below range.');
        $this->build(User::class, $profile, ['filter' => ['age' => ['in' => [30, 10]]]]);
    }

    /**
     * @return void
     */
    public function test_ranges_must_be_array(): void
    {
        $profile = ['filter' => ['age' => ['=']], 'ranges' => ['age' => 18]];

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Range must be an array.');
        $this->build(User::class, $profile, ['filter' => ['age' => ['=' => 30]]]);
    }

    /**
     * @return void
     */
    public function test_multiple_errors_are_aggregated(): void
    {
        try {
            $this->build(
                User::class,
                ['filter' => ['name' => ['=']], 'sort' => ['name']],
                ['filter' => ['secret' => ['=' => 1]], 'sort' => ['secret' => 'ASC']]
            );
            $this->fail('ValidationException was not thrown');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('filter.secret.=', $e->errors());
            $this->assertArrayHasKey('sort.secret', $e->errors());
        }
    }

    /**
     * @return void
     */
    public function test_cast_is_required(): void
    {
        // "email" has no cast on the model and none in the profile
        $this->expectExceptionMessage('Cast is not set for the attribute "email".');
        $this->build(User::class, ['filter' => ['email' => ['=']]], ['filter' => ['email' => ['=' => 'x@y.z']]]);
    }

    /**
     * @return void
     */
    public function test_scalar_validation_on_eq(): void
    {
        $this->expectExceptionMessage('Filter for User Name must be a string.');
        $this->build(User::class, ['filter' => ['name' => ['=']]], ['filter' => ['name' => ['=' => ['array']]]]);
    }

    /**
     * @return void
     */
    public function test_list_validation_on_in(): void
    {
        $this->expectExceptionMessage('Filter for User Name must be a list.');
        $this->build(User::class, ['filter' => ['name' => ['in']]], ['filter' => ['name' => ['in' => 'not-a-list']]]);
    }
}
