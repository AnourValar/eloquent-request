<?php

namespace AnourValar\EloquentRequest\Builders\Operations;

class SearchOperation extends LikeOperation
{
    /**
     * Apply typo()
     *
     * @var string
     */
    public const OPTION_TYPO = 'builder.operation.search.typo';

    /**
     * Apply fulltext search
     *
     * @var string
     */
    public const OPTION_FULLTEXT = 'builder.operation.search.fulltext';

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\LikeOperation::validate()
     */
    public function validate($value, \Closure $fail): void
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        parent::validate($value, $fail);
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\Builders\Operations\OperationInterface::apply()
     */
    public function apply(\Illuminate\Database\Eloquent\Builder &$query, string $field, $value, array $options): void
    {
        if (isset($options[self::OPTION_FULLTEXT])) {
            $value = $this->canonizeValueForFullText($value, $options);
            if (mb_strlen($value) < static::MIN_LENGTH) {
                $query->whereIn($field, []); // whereRaw('0 = 1')
            } else {
                $query->whereFullText($field, $value, $options[self::OPTION_FULLTEXT]);
            }
            return;
        }

        $query->where(function ($query) use ($field, $value, $options) {
            $fullValue = str_replace(' ', '', $value);

            $query
                ->when(
                    mb_strlen($fullValue) >= static::MIN_LENGTH && $fullValue != $value,
                    function ($query) use ($field, $fullValue, $options) {
                        $query->where($field, 'LIKE', $this->canonizeValueForLike($fullValue, $options));
                    }
                )
                ->orWhere($field, 'LIKE', $this->canonizeValueForLike($value, $options));
        });
    }

    /**
     * @param string $value
     * @param array $options
     * @return string
     * @psalm-suppress UnusedVariable
     */
    protected function canonizeValueForFullText(string $value, array $options): string
    {
        if (isset($options[self::OPTION_TYPO])) {
            $key = array_key_first($options[self::OPTION_TYPO]);
            $value = \EloquentRequestSearch::typo($value, $key, $options[self::OPTION_TYPO][$key]);
        }

        return trim(preg_replace('#[^\w\d]#u', ' ', $value)) . '*';
    }

    /**
     * @param mixed $value
     * @param array $options
     * @return string
     * @psalm-suppress UnusedVariable
     */
    protected function canonizeValueForLike($value, array $options): string
    {
        if (isset($options[self::OPTION_TYPO])) {
            $key = array_key_first($options[self::OPTION_TYPO]);
            $value = \EloquentRequestSearch::typo($value, $key, $options[self::OPTION_TYPO][$key]);
        }

        return \EloquentRequestSearch::prepare($value);
    }
}
