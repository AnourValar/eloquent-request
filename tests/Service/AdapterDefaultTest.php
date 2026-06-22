<?php

namespace AnourValar\EloquentRequest\Tests\Service;

use AnourValar\EloquentRequest\Tests\AbstractSuite;
use AnourValar\EloquentRequest\Tests\Models\User;

class AdapterDefaultTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function test_canonical_adapter_decodes_eloquent_request(): void
    {
        $result = $this->build(
            User::class,
            ['filter' => ['id' => ['=']]],
            ['eloquent_request' => json_encode(['filter' => ['id' => ['=' => 2]]])]
        );

        $this->assertSame(['Bob'], $result->pluck('name')->all());
    }

    /**
     * @return void
     */
    public function test_canonical_adapter_invalid_json_is_empty(): void
    {
        $result = $this->build(User::class, ['filter' => ['id' => ['=']]], ['eloquent_request' => 'broken']);

        $this->assertCount(4, $result);
    }

    /**
     * @return void
     */
    public function test_canonical_adapter_replaces_direct_keys(): void
    {
        // when "eloquent_request" is present the direct "filter" is ignored
        $result = $this->build(
            User::class,
            ['filter' => ['id' => ['=']]],
            ['eloquent_request' => json_encode(['filter' => ['id' => ['=' => 1]]]), 'filter' => ['id' => ['=' => 2]]]
        );

        $this->assertSame(['Alice'], $result->pluck('name')->all());
    }

    /**
     * @return void
     */
    public function test_underscore_prefixed_keys_are_removed(): void
    {
        // "_eloquent_request" must NOT be treated as the adapter payload
        $result = $this->build(
            User::class,
            ['filter' => ['id' => ['=']]],
            ['_eloquent_request' => json_encode(['filter' => ['id' => ['=' => 2]]])]
        );

        $this->assertCount(4, $result);
    }

    /**
     * @return void
     */
    public function test_default_request_per_page(): void
    {
        $result = $this->build(User::class, ['default_request' => ['per_page' => 2]], []);

        $this->assertSame(2, $result->perPage());
    }

    /**
     * @return void
     */
    public function test_default_request_filter(): void
    {
        $profile = [
            'filter' => ['status' => ['=']],
            'default_request' => ['filter' => ['status' => ['=' => 'active']]],
            'sort' => ['id'],
        ];

        $result = $this->build(User::class, $profile, ['sort' => ['id' => 'ASC']]);

        $this->assertSame(['Alice', 'Bob'], $result->pluck('name')->all());
    }

    /**
     * @return void
     */
    public function test_default_request_is_overridden_by_request(): void
    {
        $result = $this->build(User::class, ['default_request' => ['per_page' => 2]], ['per_page' => 3]);

        $this->assertSame(3, $result->perPage());
    }
}
