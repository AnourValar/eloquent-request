<?php

namespace AnourValar\EloquentRequest\Tests\Operations;

use AnourValar\EloquentRequest\Builders\Operations\SearchOperation;
use AnourValar\EloquentRequest\Tests\AbstractSuite;
use AnourValar\EloquentRequest\Tests\Models\User;
use AnourValar\EloquentRequest\Tests\Support\SearchOperationProbe;

class SearchOperationTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function test_canonize_for_like_with_typo(): void
    {
        $probe = new SearchOperationProbe();

        // "руддщ" typed on a RU layout becomes "hello"
        $this->assertSame('%hello%', $probe->exposeForLike('руддщ', [SearchOperation::OPTION_TYPO => ['ru' => 'en']]));
    }

    /**
     * @return void
     */
    public function test_canonize_for_fulltext_boolean_mode_appends_wildcard(): void
    {
        $probe = new SearchOperationProbe();

        $this->assertSame(
            'жар птица*',
            $probe->exposeForFullText('Жар-птица', [SearchOperation::OPTION_FULLTEXT => ['mode' => 'boolean']])
        );
    }

    /**
     * @return void
     */
    public function test_fulltext_short_value_matches_nothing(): void
    {
        $operation = new SearchOperation();
        $query = User::query();

        $operation->apply($query, 'name', 'a', [SearchOperation::OPTION_FULLTEXT => []]);

        $this->assertCount(0, $query->get());
    }

    /**
     * @return void
     */
    public function test_search_non_fulltext_full_value_branch(): void
    {
        // "char lie" -> also matches the spaceless "charlie"
        $profile = ['filter' => ['name' => ['search']]];

        $result = $this->build(User::class, $profile, ['filter' => ['name' => ['search' => 'char lie']]]);

        $this->assertSame(['Charlie'], $result->pluck('name')->all());
    }
}
