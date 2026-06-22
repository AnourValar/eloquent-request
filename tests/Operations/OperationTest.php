<?php

namespace AnourValar\EloquentRequest\Tests\Operations;

use AnourValar\EloquentRequest\Builders\Operations\EqOperation;
use AnourValar\EloquentRequest\Builders\Operations\InOperation;
use AnourValar\EloquentRequest\Builders\Operations\IsNullOperation;
use AnourValar\EloquentRequest\Builders\Operations\JsonContainsOperation;
use AnourValar\EloquentRequest\Builders\Operations\JsonInOperation;
use AnourValar\EloquentRequest\Builders\Operations\LikeOperation;
use AnourValar\EloquentRequest\Builders\Operations\LtOperation;
use AnourValar\EloquentRequest\Builders\Operations\SearchOperation;
use AnourValar\EloquentRequest\Helpers\FailException;
use AnourValar\EloquentRequest\Tests\AbstractSuite;

class OperationTest extends AbstractSuite
{
    /**
     * @return \Closure
     */
    private function failClosure(): \Closure
    {
        return function (string $message, array $params = [], ?string $suffix = null) {
            throw new FailException($message, $params, $suffix);
        };
    }

    /**
     * @param object $operation
     * @param mixed $value
     * @return bool
     */
    private function validates(object $operation, $value): bool
    {
        try {
            $operation->validate($value, $this->failClosure());

            return true;
        } catch (FailException $e) {
            return false;
        }
    }

    /**
     * @return void
     */
    public function test_eq_operation(): void
    {
        $operation = new EqOperation();

        $this->assertTrue($operation->cast());
        $this->assertTrue($operation->passes('whatever'));
        $this->assertTrue($operation->passes(null));

        $this->assertTrue($this->validates($operation, 'abc'));
        $this->assertTrue($this->validates($operation, null));
        $this->assertTrue($this->validates($operation, 123));
        $this->assertFalse($this->validates($operation, ['array']));
        $this->assertFalse($this->validates($operation, str_repeat('x', 1001)));
    }

    /**
     * @return void
     */
    public function test_lt_operation(): void
    {
        $operation = new LtOperation();

        $this->assertTrue($operation->cast());
        $this->assertFalse($operation->passes(null));
        $this->assertFalse($operation->passes(''));
        $this->assertTrue($operation->passes('5'));

        $this->assertTrue($this->validates($operation, '2021-01-01'));
        $this->assertFalse($this->validates($operation, str_repeat('x', 31)));
        $this->assertFalse($this->validates($operation, ['array']));
    }

    /**
     * @return void
     */
    public function test_in_operation(): void
    {
        $operation = new InOperation();

        $this->assertTrue($operation->cast());
        $this->assertFalse($operation->passes(null));
        $this->assertFalse($operation->passes([]));
        $this->assertTrue($operation->passes([1]));

        $this->assertTrue($this->validates($operation, [1, 2, 3]));
        $this->assertTrue($this->validates($operation, [null]));
        $this->assertFalse($this->validates($operation, 'not-array'));
        $this->assertFalse($this->validates($operation, [['nested']]));
        $this->assertFalse($this->validates($operation, [str_repeat('x', 101)]));
        $this->assertFalse($this->validates($operation, range(1, 1001)));
    }

    /**
     * @return void
     */
    public function test_like_operation(): void
    {
        $operation = new LikeOperation();

        $this->assertTrue($operation->cast());
        $this->assertFalse($operation->passes(null));
        $this->assertFalse($operation->passes(''));
        $this->assertTrue($operation->passes('ab'));

        $this->assertFalse($this->validates($operation, 'ab')); // below min length (3)
        $this->assertTrue($this->validates($operation, 'abc'));
        $this->assertFalse($this->validates($operation, str_repeat('x', 1001)));
    }

    /**
     * @return void
     */
    public function test_search_operation_trims_before_validation(): void
    {
        $operation = new SearchOperation();

        // "  a  " trims to "a" which is below the minimum length
        $this->assertFalse($this->validates($operation, '  a  '));
        $this->assertTrue($this->validates($operation, '  abc  '));
    }

    /**
     * @return void
     */
    public function test_is_null_operation(): void
    {
        $operation = new IsNullOperation();

        $this->assertFalse($operation->cast());
        $this->assertTrue($operation->passes(null));
        $this->assertTrue($this->validates($operation, true));
    }

    /**
     * @return void
     */
    public function test_json_in_operation(): void
    {
        $operation = new JsonInOperation();

        $this->assertTrue($this->validates($operation, ['a', 'b']));
        $this->assertFalse($this->validates($operation, 'not-array'));
        $this->assertFalse($this->validates($operation, range(1, 101)));
        $this->assertFalse($this->validates($operation, [str_repeat('x', 3001)]));
    }

    /**
     * @return void
     */
    public function test_json_contains_operation(): void
    {
        $operation = new JsonContainsOperation();

        $this->assertTrue($this->validates($operation, ['a', 'b']));
        $this->assertFalse($this->validates($operation, [str_repeat('x', 3001)]));
    }
}
