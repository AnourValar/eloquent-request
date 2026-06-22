<?php

namespace AnourValar\EloquentRequest\Tests\Service;

use AnourValar\EloquentRequest\Builders\FilterAndScopeBuilder;
use AnourValar\EloquentRequest\Tests\AbstractSuite;
use AnourValar\EloquentRequest\Tests\Models\User;

class OptionsTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function test_relation_field_filter(): void
    {
        $profile = ['filter' => ['phones.number' => ['like']], 'sort' => ['id']];

        $result = $this->build(User::class, $profile, ['filter' => ['phones.number' => ['like' => '111']], 'sort' => ['id' => 'ASC']]);

        $this->assertSame(['Alice'], $result->pluck('name')->all());
    }

    /**
     * @return void
     */
    public function test_relation_filters_are_separate_by_default(): void
    {
        // "number like 222" (Alice's home phone) AND "type = mobile" (any phone) -> Alice qualifies
        $profile = ['filter' => ['phones.number' => ['like'], 'phones.type' => ['=']]];
        $request = ['filter' => ['phones.number' => ['like' => '222'], 'phones.type' => ['=' => 'mobile']]];

        $result = $this->build(User::class, $profile, $request);

        $this->assertSame(['Alice'], $result->pluck('name')->all());
    }

    /**
     * @return void
     */
    public function test_group_relation_combines_filters_into_one_relation(): void
    {
        // grouped: the SAME phone must be "number like 222" AND "type mobile" -> none
        $profile = [
            'filter' => ['phones.number' => ['like'], 'phones.type' => ['=']],
            'options' => [FilterAndScopeBuilder::OPTION_GROUP_RELATION],
        ];
        $request = ['filter' => ['phones.number' => ['like' => '222'], 'phones.type' => ['=' => 'mobile']]];

        $result = $this->build(User::class, $profile, $request);

        $this->assertCount(0, $result);
    }

    /**
     * @return void
     */
    public function test_casts_not_required(): void
    {
        // "email" has no cast, but the option allows it
        $profile = [
            'filter' => ['email' => ['=']],
            'options' => [FilterAndScopeBuilder::OPTION_CASTS_NOT_REQUIRED],
        ];

        $result = $this->build(User::class, $profile, ['filter' => ['email' => ['=' => 'bob@example.com']]]);

        $this->assertSame(['Bob'], $result->pluck('name')->all());
    }

    /**
     * @return void
     */
    public function test_alias(): void
    {
        $profile = [
            'filter' => ['full' => ['like']],
            'alias' => ['full' => 'name'],
            'custom_casts' => ['full' => 'string'],
        ];

        $result = $this->build(User::class, $profile, ['filter' => ['full' => ['like' => 'cha']]]);

        $this->assertSame(['Charlie'], $result->pluck('name')->all());
    }

    /**
     * @return void
     */
    public function test_custom_attributes_used_in_error(): void
    {
        $profile = [
            'filter' => ['name' => ['=']],
            'custom_attributes' => ['name' => 'The Custom Name'],
        ];

        $this->expectExceptionMessage('Filter for The Custom Name does not support the specified operation.');
        $this->build(User::class, $profile, ['filter' => ['name' => ['like' => 'abc']]]);
    }

    /**
     * @return void
     */
    public function test_custom_attributes_handler_used_in_error(): void
    {
        $profile = [
            'filter' => ['name' => ['=']],
            'custom_attributes_handler' => fn ($field) => $field === 'name' ? 'Handled Name' : null,
        ];

        $this->expectExceptionMessage('Filter for Handled Name does not support the specified operation.');
        $this->build(User::class, $profile, ['filter' => ['name' => ['like' => 'abc']]]);
    }

    /**
     * @return void
     */
    public function test_custom_attributes_path_used_in_error(): void
    {
        app('translator')->addLines(['attrs.name' => 'Path Name'], 'en');

        $profile = [
            'filter' => ['name' => ['=']],
            'custom_attributes_path' => 'attrs',
        ];

        $this->expectExceptionMessage('Filter for Path Name does not support the specified operation.');
        $this->build(User::class, $profile, ['filter' => ['name' => ['like' => 'abc']]]);
    }

    /**
     * @return void
     */
    public function test_attribute_name_falls_back_to_model(): void
    {
        // User::getAttributeNames() maps "age" => "User Age"
        $profile = ['filter' => ['age' => ['=']]];

        $this->expectExceptionMessage('Filter for User Age does not support the specified operation.');
        $this->build(User::class, $profile, ['filter' => ['age' => ['like' => 1]]]);
    }
}
