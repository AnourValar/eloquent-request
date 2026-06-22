<?php

namespace AnourValar\EloquentRequest\Tests\Support;

use AnourValar\EloquentRequest\Builders\Operations\SearchOperation;

class SearchOperationProbe extends SearchOperation
{
    /**
     * @param string $value
     * @param array $options
     * @return string
     */
    public function exposeForFullText(string $value, array $options): string
    {
        return $this->canonizeValueForFullText($value, $options);
    }

    /**
     * @param mixed $value
     * @param array $options
     * @return string
     */
    public function exposeForLike($value, array $options): string
    {
        return $this->canonizeValueForLike($value, $options);
    }
}
