<?php

namespace AnourValar\EloquentRequest\Tests\Helpers;

use AnourValar\EloquentRequest\Helpers\Request;
use AnourValar\EloquentRequest\Tests\AbstractSuite;
use AnourValar\EloquentRequest\Tests\Models\User;

class RequestTest extends AbstractSuite
{
    /**
     * Build and capture the resulting Request object
     *
     * @param array $profile
     * @param array $request
     * @return \AnourValar\EloquentRequest\Helpers\Request
     */
    private function request(array $profile, array $request): Request
    {
        // the default (paginate) action populates per_page/page into the build request
        $this->build(User::class, $profile, $request, $buildRequest);

        return $buildRequest;
    }

    /**
     * @return \AnourValar\EloquentRequest\Helpers\Request
     */
    private function fullRequest(): Request
    {
        $profile = [
            'filter' => ['status' => ['='], 'phones.number' => ['like']],
            'relation' => ['phones'],
            'scope' => ['active'],
            'sort' => ['id', 'name'],
            'custom_attributes' => ['name' => 'Custom Name'],
        ];

        $request = [
            'filter' => ['status' => ['=' => 'active'], 'phones.number' => ['like' => '111']],
            'relation' => ['phones' => ['min' => 1]],
            'scope' => ['active' => true],
            'sort' => ['name' => 'ASC'],
            'per_page' => 7,
            'page' => 2,
        ];

        return $this->request($profile, $request);
    }

    /**
     * @return void
     */
    public function test_get(): void
    {
        $request = $this->fullRequest();

        $this->assertSame(7, $request->get('per_page'));
        $this->assertSame('active', $request->get('filter.status.='));
        $this->assertSame('fallback', $request->get('does.not.exist', 'fallback'));
        $this->assertIsArray($request->get());
    }

    /**
     * @return void
     */
    public function test_typed_getters(): void
    {
        $request = $this->fullRequest();

        $this->assertSame('active', $request->filter('status.='));
        $this->assertSame(['=' => 'active'], $request->filter('status'));
        $this->assertSame(1, $request->relation('phones.min'));
        $this->assertTrue($request->scope('active'));
        $this->assertSame('ASC', $request->sort('name'));
    }

    /**
     * @return void
     */
    public function test_typed_getters_without_path(): void
    {
        $request = $this->fullRequest();

        $this->assertArrayHasKey('status', $request->filter());
        $this->assertArrayHasKey('phones', $request->relation());
        $this->assertArrayHasKey('active', $request->scope());
        $this->assertArrayHasKey('name', $request->sort());
    }

    /**
     * @return void
     */
    public function test_profile(): void
    {
        $request = $this->fullRequest();

        $this->assertArrayHasKey('filter', $request->profile());
        $this->assertSame(['name' => 'Custom Name'], $request->profile()['custom_attributes']);
    }

    /**
     * @return void
     */
    public function test_has_filters_and_sorts(): void
    {
        $request = $this->fullRequest();

        $this->assertTrue($request->hasFilters());
        $this->assertTrue($request->hasSorts());
    }

    /**
     * @return void
     */
    public function test_has_filters_false_when_none(): void
    {
        $request = $this->request(['filter' => ['status' => ['=']]], []);

        $this->assertFalse($request->hasFilters());
        $this->assertFalse($request->hasSorts());
    }

    /**
     * @return void
     */
    public function test_has_only(): void
    {
        $request = $this->request([], ['per_page' => 7]);

        $this->assertTrue($request->hasOnly(['per_page']));
        $this->assertFalse($request->hasOnly(['page']));
        $this->assertFalse($request->hasOnly([])); // per_page differs from the default

        $defaults = $this->request([], []);
        $this->assertTrue($defaults->hasOnly([]));
    }

    /**
     * @return void
     */
    public function test_array_access(): void
    {
        $request = $this->request([], []);

        $this->assertFalse(isset($request['custom']));
        $this->assertNull($request['custom']);

        $request['custom'] = 'value';
        $this->assertTrue(isset($request['custom']));
        $this->assertSame('value', $request['custom']);

        $request[] = 'appended';
        $this->assertSame('appended', $request[0]);

        unset($request['custom']);
        $this->assertFalse(isset($request['custom']));
    }

    /**
     * @return void
     */
    public function test_get_display_attribute(): void
    {
        $request = $this->fullRequest();

        $this->assertSame('Custom Name', $request->getDisplayAttribute('name')); // from profile
        $this->assertSame('User Age', $request->getDisplayAttribute('age')); // from model
        $this->assertSame('email', $request->getDisplayAttribute('email')); // as is
    }

    /**
     * @return void
     */
    public function test_cache_key(): void
    {
        $a = $this->request(['filter' => ['status' => ['=']]], ['filter' => ['status' => ['=' => 'active']]]);
        $b = $this->request(['filter' => ['status' => ['=']]], ['filter' => ['status' => ['=' => 'active']]]);
        $c = $this->request(['filter' => ['status' => ['=']]], ['filter' => ['status' => ['=' => 'blocked']]]);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $a->cacheKey());
        $this->assertSame($a->cacheKey(), $b->cacheKey());
        $this->assertNotSame($a->cacheKey(), $c->cacheKey());
    }
}
