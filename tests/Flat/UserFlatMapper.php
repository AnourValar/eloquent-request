<?php

namespace AnourValar\EloquentRequest\Tests\Flat;

use AnourValar\EloquentRequest\FlatInterface;
use AnourValar\EloquentRequest\FlatMapper;
use AnourValar\EloquentRequest\Tests\Models\UserFlat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

class UserFlatMapper implements FlatInterface
{
    /**
     * Multiple rows per model (test control)
     *
     * @var array|null
     */
    public ?array $multipleRows = null;

    /**
     * Whether a model should be stored (test control)
     *
     * @var bool
     */
    public bool $shouldStore = true;

    /**
     * Marker: onTableCreated() was called
     *
     * @var bool
     */
    public bool $tableCreated = false;

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\FlatInterface::scheme()
     */
    public function scheme(): array
    {
        return [
            new FlatMapper([
                'source' => 'id',
                'target' => 'user_id',
                'purpose' => FlatMapper::PURPOSE_IDENTIFIER,
                'cast' => 'integer',
                'filter' => ['user_id' => ['=', 'in']],
                'sort' => 'user_id',
                'ranges' => [],
                'attribute_names' => null,
                // NOTE: integer/text (not bigint/varchar) so the structure round-trips on SQLite
                'migration' => fn (Blueprint $table) => $table->integer('user_id'),
            ]),

            new FlatMapper([
                'source' => 'name',
                'target' => 'name',
                'purpose' => FlatMapper::PURPOSE_PAYLOAD,
                'cast' => 'string',
                'filter' => ['name' => ['like']],
                'sort' => 'name',
                'ranges' => [],
                'attribute_names' => ['name' => 'Name'],
                'migration' => fn (Blueprint $table) => $table->text('name'),
            ]),

            new FlatMapper([
                'source' => null,
                'target' => 'tag',
                'purpose' => FlatMapper::PURPOSE_PAYLOAD,
                'cast' => null,
                'filter' => ['tag' => ['=']],
                'sort' => null,
                'ranges' => ['tag' => ['max' => 100]],
                'attribute_names' => null,
                'migration' => fn (Blueprint $table) => $table->text('tag')->nullable(),
            ]),
        ];
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\FlatInterface::flatModel()
     */
    public function flatModel(): Model
    {
        return new UserFlat();
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\FlatInterface::onTableCreated()
     */
    public function onTableCreated(): void
    {
        $this->tableCreated = true;
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\FlatInterface::multiple()
     */
    public function multiple(Model $model): ?array
    {
        return $this->multipleRows;
    }

    /**
     * {@inheritDoc}
     * @see \AnourValar\EloquentRequest\FlatInterface::shouldBeStored()
     */
    public function shouldBeStored(Model $model): bool
    {
        return $this->shouldStore;
    }
}
