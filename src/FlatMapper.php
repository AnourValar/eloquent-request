<?php

namespace AnourValar\EloquentRequest;

class FlatMapper
{
    /**
     * @var string
     */
    public const PURPOSE_IDENTIFIER = '1';
    public const PURPOSE_PAYLOAD = '2';
    public const PURPOSE_META = '3';

    /**
     * @var array
     */
    private $data;

    /**
     * Fill up
     *
     * @param array $data
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Model (original): column name (path)
     *
     * @return string|callable|null
     */
    public function source(): string|callable|null
    {
        return $this->data['source'];
    }

    /**
     * Flat: column name
     *
     * @return string
     */
    public function target(): string
    {
        return $this->data['target'];
    }

    /**
     * Column purpose
     *
     * @return string
     */
    public function purpose(): string
    {
        return $this->data['purpose'];
    }

    /**
     * Models's cast
     *
     * @return string|array|null
     */
    public function cast(): string|array|null
    {
        return $this->data['cast'];
    }

    /**
     * Profile's filter
     *
     * @return array
     */
    public function filter(): array
    {
        return $this->data['filter'];
    }

    /**
     * Profile's sort
     *
     * @return array
     */
    public function sort(): array
    {
        return (array) $this->data['sort'];
    }

    /**
     * Profile's ranges
     *
     * @return array
     */
    public function ranges(): array
    {
        return $this->data['ranges'];
    }

    /**
     * Profile's (model's) attribute names
     *
     * @return string|array|null
     */
    public function attributeNames(): string|array|null
    {
        return $this->data['attribute_names'];
    }

    /**
     * Closure for filling up Blueprint
     *
     * @param \Illuminate\Database\Schema\Blueprint $table
     * @return void
     */
    public function migration(\Illuminate\Database\Schema\Blueprint $table): void
    {
        $this->data['migration']($table);
    }
}
