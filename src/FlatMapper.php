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
     * @return string
     */
    public function isIdentifier(): bool
    {
        return $this->data['is_identifier'];
    }

    /**
     * Models's cast
     *
     * @return string
     */
    public function cast()
    {
        return $this->data['cast'];
    }

    /**
     * Profile's filter
     *
     * @return string
     */
    public function filter(): array
    {
        return $this->data['filter'];
    }

    /**
     * Profile's sort
     *
     * @return string
     */
    public function sort(): array
    {
        return $this->data['sort'];
    }

    /**
     * Profile's ranges
     *
     * @return string
     */
    public function ranges(): array
    {
        return $this->data['ranges'];
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
