<?php

namespace AnourValar\EloquentRequest;

class FlatMapper
{
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
     * @return string
     */
    public function source(): string
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
     * Is identifier (service)
     *
     * @return bool
     */
    public function isIdentifier(): bool
    {
        return $this->data['is_identifier'];
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
