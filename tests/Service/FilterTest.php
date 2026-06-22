<?php

namespace AnourValar\EloquentRequest\Tests\Service;

use AnourValar\EloquentRequest\Tests\AbstractSuite;
use AnourValar\EloquentRequest\Tests\Models\User;

class FilterTest extends AbstractSuite
{
    /**
     * @return array
     */
    private function profile(): array
    {
        return [
            'filter' => [
                'id' => ['=', '!=', '<', '<=', '>', '>=', 'in', 'not-in'],
                'name' => ['=', '!=', 'like', 'not-like', 'search'],
                'status' => ['=', '!=', 'in', 'not-in'],
                'age' => ['=', '!=', '<', '<=', '>', '>=', 'in', 'not-in'],
                'balance' => ['<', '>'],
                'email_verified_at' => ['=', '!=', 'is-null' => 'is-null', '>'],
                'created_at' => ['<', '<=', '>', '>='],
                'settings->tags' => ['json-in', 'json-contains', 'json-not-in', 'json-not-contains'],
            ],

            'custom_casts' => ['settings->*' => 'string'],
        ];
    }

    /**
     * @param array $filter
     * @return array
     */
    private function names(array $filter): array
    {
        $result = $this->build(User::class, $this->profile() + ['sort' => ['id']], ['filter' => $filter, 'sort' => ['id' => 'ASC']]);

        return $result->pluck('name')->all();
    }

    /**
     * @return void
     */
    public function test_eq(): void
    {
        $this->assertSame(['Bob'], $this->names(['id' => ['=' => 2]]));
        $this->assertSame(['Alice', 'Bob'], $this->names(['status' => ['=' => 'active']]));
    }

    /**
     * @return void
     */
    public function test_eq_null(): void
    {
        // "=" null becomes "whereNull"
        $this->assertSame(['Dave'], $this->names(['age' => ['=' => null]]));
    }

    /**
     * @return void
     */
    public function test_not_eq(): void
    {
        $this->assertSame(['Charlie', 'Dave'], $this->names(['status' => ['!=' => 'active']]));
    }

    /**
     * @return void
     */
    public function test_not_eq_null(): void
    {
        // "!=" null becomes "whereNotNull"
        $query = $this->buildQuery(User::class, $this->profile(), ['filter' => ['email_verified_at' => ['!=' => null]]]);
        $this->assertStringContainsStringIgnoringCase('is not null', $query->toSql());
    }

    /**
     * @return void
     */
    public function test_lt_le_gt_ge(): void
    {
        $this->assertSame(['Bob'], $this->names(['age' => ['<' => 30]]));
        $this->assertSame(['Alice', 'Bob'], $this->names(['age' => ['<=' => 30]]));
        $this->assertSame(['Charlie'], $this->names(['age' => ['>' => 30]]));
        $this->assertSame(['Alice', 'Charlie'], $this->names(['age' => ['>=' => 30]]));
    }

    /**
     * @return void
     */
    public function test_in_not_in(): void
    {
        $this->assertSame(['Alice', 'Charlie'], $this->names(['id' => ['in' => [1, 3]]]));
        $this->assertSame(['Bob', 'Dave'], $this->names(['id' => ['not-in' => [1, 3]]]));
    }

    /**
     * @return void
     */
    public function test_in_with_null(): void
    {
        $this->assertSame(['Dave'], $this->names(['age' => ['in' => [null]]]));
        $this->assertSame(['Alice', 'Dave'], $this->names(['age' => ['in' => [30, null]]]));
    }

    /**
     * @return void
     */
    public function test_not_in_with_null(): void
    {
        // not-in [null] => whereNotNull
        $this->assertSame(['Alice', 'Bob', 'Charlie'], $this->names(['age' => ['not-in' => [null]]]));
    }

    /**
     * @return void
     */
    public function test_like_not_like(): void
    {
        // LIKE requires a minimum length of 3 characters
        $this->assertSame(['Charlie'], $this->names(['name' => ['like' => 'cha']]));
        $this->assertSame(['Alice', 'Bob', 'Dave'], $this->names(['name' => ['not-like' => 'cha']]));
    }

    /**
     * @return void
     */
    public function test_like_escapes_wildcards(): void
    {
        // there is no user with literal "%" in the name
        $this->assertSame([], $this->names(['name' => ['like' => 'ch%ar']]));
    }

    /**
     * @return void
     */
    public function test_is_null(): void
    {
        $this->assertSame(['Bob', 'Dave'], $this->names(['email_verified_at' => ['is-null' => true]]));
        $this->assertSame(['Alice', 'Charlie'], $this->names(['email_verified_at' => ['is-null' => false]]));
    }

    /**
     * @return void
     */
    public function test_search(): void
    {
        $this->assertSame(['Alice'], $this->names(['name' => ['search' => 'ali']]));
    }

    /**
     * @return void
     */
    public function test_datetime_filter(): void
    {
        // "> 2022-01-01" gets canonized to a date boundary
        $this->assertSame(['Charlie', 'Dave'], $this->names(['created_at' => ['>' => '2022-01-01']]));
        $this->assertSame(['Alice', 'Bob'], $this->names(['created_at' => ['<' => '2022-01-01']]));
    }

    /**
     * @return void
     */
    public function test_json_contains(): void
    {
        $this->assertSame(['Alice', 'Charlie'], $this->names(['settings->tags' => ['json-contains' => 'vip']]));
        $this->assertSame(['Alice', 'Bob'], $this->names(['settings->tags' => ['json-in' => ['beta']]]));
    }

    /**
     * @return void
     */
    public function test_json_not_contains(): void
    {
        $this->assertSame(['Bob', 'Dave'], $this->names(['settings->tags' => ['json-not-contains' => 'vip']]));
    }

    /**
     * @return void
     */
    public function test_json_not_in(): void
    {
        $this->assertSame(['Bob', 'Dave'], $this->names(['settings->tags' => ['json-not-in' => ['vip']]]));
        $this->assertSame(['Dave'], $this->names(['settings->tags' => ['json-not-in' => ['vip', 'beta']]]));
    }

    /**
     * @return void
     */
    public function test_empty_filter_value_is_skipped(): void
    {
        // empty string for LIKE is ignored (passes() === false) => all rows
        $this->assertSame(['Alice', 'Bob', 'Charlie', 'Dave'], $this->names(['name' => ['like' => '']]));
    }
}
