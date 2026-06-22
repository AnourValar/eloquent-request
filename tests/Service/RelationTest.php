<?php

namespace AnourValar\EloquentRequest\Tests\Service;

use AnourValar\EloquentRequest\Tests\AbstractSuite;
use AnourValar\EloquentRequest\Tests\Models\User;

class RelationTest extends AbstractSuite
{
    /**
     * @param array $relation
     * @return array
     */
    private function names(array $relation): array
    {
        return $this
            ->build(User::class, ['relation' => ['phones'], 'sort' => ['id']], ['relation' => $relation, 'sort' => ['id' => 'ASC']])
            ->pluck('name')
            ->all();
    }

    /**
     * @return void
     */
    public function test_min(): void
    {
        // min 1 => has at least one phone
        $this->assertSame(['Alice', 'Bob'], $this->names(['phones' => ['min' => 1]]));

        // min 2 => has at least two phones
        $this->assertSame(['Alice'], $this->names(['phones' => ['min' => 2]]));
    }

    /**
     * @return void
     */
    public function test_max(): void
    {
        // max 0 => has no phones
        $this->assertSame(['Charlie', 'Dave'], $this->names(['phones' => ['max' => 0]]));

        // max 1 => has at most one phone
        $this->assertSame(['Bob', 'Charlie', 'Dave'], $this->names(['phones' => ['max' => 1]]));
    }

    /**
     * @return void
     */
    public function test_min_zero_is_no_constraint(): void
    {
        $this->assertSame(['Alice', 'Bob', 'Charlie', 'Dave'], $this->names(['phones' => ['min' => 0]]));
    }

    /**
     * @return void
     */
    public function test_null_value_is_ignored(): void
    {
        $this->assertSame(['Alice', 'Bob', 'Charlie', 'Dave'], $this->names(['phones' => ['min' => null]]));
    }

    /**
     * @return void
     */
    public function test_relation_not_supported(): void
    {
        $this->expectExceptionMessage('Filter for relation comments is not available.');

        $this->build(User::class, ['relation' => ['phones']], ['relation' => ['comments' => ['min' => 1]]]);
    }

    /**
     * @return void
     */
    public function test_relation_operation_not_supported(): void
    {
        $this->expectExceptionMessage('Filter for relation phones contains a non-existent operation.');

        $this->build(User::class, ['relation' => ['phones']], ['relation' => ['phones' => ['avg' => 1]]]);
    }

    /**
     * @return void
     */
    public function test_relation_out_of_range(): void
    {
        $this->expectExceptionMessage('must be between 0 and 10000');

        $this->build(User::class, ['relation' => ['phones']], ['relation' => ['phones' => ['min' => -1]]]);
    }

    /**
     * @return void
     */
    public function test_relation_out_of_range_not_numeric(): void
    {
        $this->expectExceptionMessage('must be between 0 and 10000');

        $this->build(User::class, ['relation' => ['phones']], ['relation' => ['phones' => ['min' => 'abc']]]);
    }
}
