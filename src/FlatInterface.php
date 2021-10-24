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
    public function model(): \Illuminate\Database\Eloquent\Model;

    /**
     * Any actions after flat table was created
     *
     * @return void
     */
    public function onTableCreated(): void;
}
