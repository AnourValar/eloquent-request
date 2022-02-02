<?php

namespace AnourValar\EloquentRequest;

interface FlatInterface
{
    /**
     * Gets a structure
     *
     * @return \AnourValar\EloquentRequest\FlatMapper[]
     */
    public function scheme(): array;

    /**
     * Gets an instance of the flat model
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function flatModel(): \Illuminate\Database\Eloquent\Model;

    /**
     * Any actions after flat table was created
     *
     * @return void
     */
    public function onTableCreated(): void;

    /**
     * Resolve if the model should present in a flat table
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return bool
     */
    public function shouldBeStored(\Illuminate\Database\Eloquent\Model $model): bool;
}
