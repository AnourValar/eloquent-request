<?php

namespace AnourValar\EloquentRequest;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FlatService
{
    /**
     * Check if current structure of the flat table is actual
     *
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @return bool
     */
    public function isActualTable(FlatInterface $flatInterface): bool
    {
        return json_encode($this->getFactStructure($flatInterface)) === json_encode($this->getActualStructure($flatInterface));
    }

    /**
     * (Re)create the flat table
     *
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @throws \LogicException
     * @return void
     */
    public function createTable(FlatInterface $flatInterface): void
    {
        $this->dropTable($flatInterface);

        $flatModel = $this->getFlatModelForWrite($flatInterface, true);

        Schema::connection($flatModel->getConnectionName())->create($flatModel->getTable(), function (Blueprint $table) use ($flatInterface) {
            foreach ($flatInterface->scheme() as $column) {
                $column->migration($table);
            }
        });
        $flatInterface->onTableCreated();

        if (! $this->isActualTable($flatInterface)) {
            throw new \LogicException('Incorrect scheme');
        }
    }

    /**
     * Drop the flat table
     *
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @return void
     */
    public function dropTable(FlatInterface $flatInterface): void
    {
        $flatModel = $this->getFlatModelForWrite($flatInterface, true);

        Schema::connection($flatModel->getConnectionName())->dropIfExists($flatModel->getTable());
    }

    /**
     * Rename shadow table to the original
     * Must be called after deploy
     *
     * @param FlatInterface $flatInterface
     * @param bool $cleanUp
     * @throws \LogicException
     * @return void
     */
    public function switchShadow(FlatInterface $flatInterface, bool $cleanUp = true): void
    {
        $flatModel = $flatInterface->flatModel();
        $table = $flatModel->getTable();

        $shadowTable = $this->shadow($flatInterface);
        if (! $shadowTable) {
            throw new \LogicException('Incorrect usage.');
        }

        if (\Schema::hasTable($table)) {
            \Schema::connection($flatModel->getConnectionName())->rename($table, "{$table}_delete"); // flat -> flat_delete
        }
        \Schema::connection($flatModel->getConnectionName())->rename($shadowTable, $table); // flat_<shadow> -> flat

        if ($cleanUp) {
            \Atom::onCommit(function () use ($flatModel, $table) {
                \Schema::connection($flatModel->getConnectionName())->dropIfExists("{$table}_delete");
            }, $flatModel->getConnectionName());
        }
    }

    /**
     * Get the name of a shadow table (if exists)
     *
     * @param FlatInterface $flatInterface
     * @param bool $force
     * @return string|null
     */
    public function shadow(FlatInterface $flatInterface, bool $force = false): ?string
    {
        if (! config('eloquent_request.flat.shadow')) {
            return null;
        }

        $sha1 = \Cache::driver('array')->rememberForever(implode(' / ', [__METHOD__, get_class($flatInterface)]), function () use ($flatInterface) {
            return sha1(json_encode($this->getActualStructure($flatInterface)));
        });
        $shadowTable = $flatInterface->flatModel()->getTable() . '_' . $sha1;

        if (! $force && ! \Schema::hasTable($shadowTable)) {
            return null;
        }

        return $shadowTable;
    }

    /**
     * Fill up flat table with all current data
     * Must be called after deploy in "shadow" scenario
     *
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @param string $model
     * @param callable|null $closure
     * @param int $chunkSize
     * @return int
     */
    public function resync(FlatInterface $flatInterface, string $model, ?callable $closure = null, int $chunkSize = 5000): int
    {
        if ($closure) {
            $closure = \Closure::bind($closure, $this); // wrap for the atomic lock
        } else {
            $closure = [$this, 'sync'];
        }

        $model = new $model();
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses($model))) {
            $model = $model->withTrashed();
        }

        $affected = 0;
        $model->chunkById($chunkSize, function ($items) use (&$affected, $closure, $flatInterface) {
            foreach ($items as $item) {
                $closure($flatInterface, $item);
                $affected++;
            }
        });

        return $affected;
    }

    /**
     * Fill up flat table for a specific model
     *
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function sync(FlatInterface $flatInterface, \Illuminate\Database\Eloquent\Model $model): void
    {
        list($identifiers, $dataSets) = $this->syncState($flatInterface, $model);

        $this->getFlatModelForWrite($flatInterface)->where($identifiers)->delete();

        foreach ($dataSets as $dataSet) {
            $this
                ->getFlatModelForWrite($flatInterface)
                ->withCasts($this->getCasts($flatInterface))
                ->create(array_merge($dataSet, $identifiers));
        }
    }

    /**
     * Fill up flat table for a specific model (soft)
     *
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @param \Illuminate\Database\Eloquent\Model|null $model
     * @return void
     */
    public function syncSoft(FlatInterface $flatInterface, ?\Illuminate\Database\Eloquent\Model $model): void
    {
        if (! $model) {
            return;
        }

        list($identifiers, $dataSets) = $this->syncState($flatInterface, $model);

        if (! $dataSets) {
            return;
        }

        if ($this->getFlatModelForWrite($flatInterface, true)->where($identifiers)->first()) {
            return;
        }

        foreach ($dataSets as $dataSet) {
            $this
                ->getFlatModelForWrite($flatInterface, true)
                ->withCasts($this->getCasts($flatInterface))
                ->create(array_merge($dataSet, $identifiers));
        }
    }

    /**
     * Gets casts for a model
     *
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @param string $prefix
     * @return array
     */
    public function getCasts(FlatInterface $flatInterface, string $prefix = ''): array
    {
        return $this->getProfile($flatInterface, $prefix, 'cast');
    }

    /**
     * Gets filters for a profile
     *
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @param string $prefix
     * @return array
     */
    public function getFilters(FlatInterface $flatInterface, string $prefix = ''): array
    {
        return $this->getProfile($flatInterface, $prefix, 'filter');
    }

    /**
     * Gets sorts for a profile
     *
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @param string $prefix
     * @return array
     */
    public function getSorts(FlatInterface $flatInterface, string $prefix = ''): array
    {
        $result = [];
        foreach ($flatInterface->scheme() as $column) {
            $result = array_merge($result, $column->sort());
        }

        return array_filter($result);
    }

    /**
     * Gets ranges for a profile
     *
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @param string $prefix
     * @return array
     */
    public function getRanges(FlatInterface $flatInterface, string $prefix = ''): array
    {
        return $this->getProfile($flatInterface, $prefix, 'ranges');
    }

    /**
     * Gets attribute names for a profile or model
     *
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @param string $prefix
     * @return array
     */
    public function getAttributeNames(FlatInterface $flatInterface, string $prefix = ''): array
    {
        $result = $this->getProfile($flatInterface, $prefix, 'attributeNames');
        foreach ($result as &$value) {
            $value = trans($value);
        }
        unset($value);

        return $result;
    }

    /**
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @return array
     */
    protected function getFactStructure(\AnourValar\EloquentRequest\FlatInterface $flatInterface): array
    {
        $table = $this->getFlatModelForWrite($flatInterface)->getTable();
        $structure = [];

        $inspect = [];
        foreach (\DB::getSchemaBuilder()->getColumns($table) as $item) {
            $item['length'] = null;
            preg_match('#\((\d+)\)#', $item['type'], $length);
            if (! empty($length[1])) {
                $item['length'] = (int) $length[1];
            }

            $inspect[$item['name']] = $item;
        }

        foreach (\DB::getSchemaBuilder()->getColumnListing($table) as $column) {
            $structure[] = [
                'column' => $column,
                'type' => $this->normalizeType($inspect[$column]['type_name']),
                'length' => $inspect[$column]['length'],
                'nullable' => $inspect[$column]['nullable'],
            ];
        }

        return $structure;
    }

    /**
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @return array
     */
    protected function getActualStructure(\AnourValar\EloquentRequest\FlatInterface $flatInterface): array
    {
        $structure = [];

        $blueprint = new \Illuminate\Database\Schema\Blueprint('');
        foreach ($flatInterface->scheme() as $column) {
            $column->migration($blueprint);
        }

        foreach ($blueprint->getColumns() as $column) {
            $attribute = $column->getAttributes();

            $type = $this->normalizeType($attribute['type']);
            $length = ($attribute['length'] ?? null);
            if (! isset($length) && in_array($type, ['string', 'tinytext'])) {
                $length = \Illuminate\Database\Schema\Builder::$defaultStringLength;
            }

            $structure[] = [
                'column' => $attribute['name'],
                'type' => $type,
                'length' => $length,
                'nullable' => ($attribute['nullable'] ?? false),
            ];
        }

        return $structure;
    }

    /**
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @param string $prefix
     * @param string $method
     * @return array
     */
    protected function getProfile(FlatInterface $flatInterface, string $prefix, string $method): array
    {
        $result = [];

        foreach ($flatInterface->scheme() as $column) {
            $curr = $column->$method();
            if (! is_array($curr)) {
                $result[$prefix . $column->target()] = $curr;
            } else {
                foreach ($curr as $key => $item) {
                    $result[$prefix . $key] = $item;
                }
            }
        }

        return array_filter($result);
    }

    /**
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @param \Illuminate\Database\Eloquent\Model $model
     * @throws \LogicException
     * @return array
     */
    protected function syncState(FlatInterface $flatInterface, \Illuminate\Database\Eloquent\Model $model): array
    {
        $identifiers = [];
        $data = [];
        $columns = [];

        foreach ($flatInterface->scheme() as $column) {
            $value = null;
            if (! is_null($column->source())) {
                $value = $model;
                if (is_callable($column->source())) {
                    $value = $column->source()($value);
                } else {
                    foreach (explode('.', $column->source()) as $item) {
                        $value = ($value[$item] ?? null);
                    }
                }
            }

            if ($column->purpose() == FlatMapper::PURPOSE_IDENTIFIER) {
                if (! isset($value)) {
                    throw new \LogicException('Incorrect usage.');
                }
                $identifiers[$column->target()] = $value;
            } else {
                if ($column->purpose() == FlatMapper::PURPOSE_PAYLOAD) {
                    $columns[] = $column->target();
                }
                $data[$column->target()] = $value;
            }
        }

        $dataSets = [];
        foreach ($flatInterface->multiple($model) ?? [[]] as $item) {
            $dataSets[] = array_merge($data, $item);
        }

        $dataSets = array_filter($dataSets, function ($item) use ($columns, $identifiers) {
            if (array_intersect_key($item, $identifiers)) {
                throw new \LogicException('Incorrect usage.');
            }

            foreach ($columns as $column) {
                if (isset($item[$column])) {
                    return true;
                }
            }

            return false;
        });

        if (! $model->exists || ! $flatInterface->shouldBeStored($model)) {
            $dataSets = [];
        }

        return [$identifiers, $dataSets];
    }

    /**
     * @param FlatInterface $flatInterface
     * @param bool $force
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getFlatModelForWrite(FlatInterface $flatInterface, bool $force = false): \Illuminate\Database\Eloquent\Model
    {
        $flatModel = $flatInterface->flatModel();

        if (! $shadowTable = $this->shadow($flatInterface, $force)) {
            return $flatModel;
        }

        return $flatModel->setTable($shadowTable);
    }

    /**
     * @param string $type
     * @return string
     */
    protected function normalizeType(string $type): string
    {
        /*
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->bigInteger('a1')]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->smallInteger('a2')]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->mediumINteger('a3')]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->tinyInteger('a4')]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->unsignedBigInteger('a5')]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->integer('a6')]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->string('a7')]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->string('a8', 123)]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->boolean('a9')->nullable()]),

            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->unsignedMediumInteger('a10')->nullable()]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->text('a11')]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->tinyText('a12')]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->mediumText('a13')]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->float('a14')]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->double('a15')]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->decimal('a16', 10, 2)]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->bigInteger('a17')]),

            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->json('a18')]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->jsonb('a19')]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->json('a20')->nullable()]),

            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->uuid('a21')->nullable()]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->timestamp('a22')->nullable()]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->datetime('a23')->nullable()]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->date('a24')->nullable()]),
            new FlatMapper(['migration' => fn (Blueprint $blueprint) => $blueprint->time('a25')->nullable()]),
         */

        $type = mb_strtolower($type);

        return match ($type) {
            'int2' => 'smallinteger',
            'int4' => 'integer',
            'int8' => 'biginteger',
            'mediuminteger' => 'integer',
            'tinyinteger' => 'smallinteger',

            'float8' => 'float',
            'double' => 'float',
            'numeric' => 'decimal',

            'varchar' => 'string',
            'tinytext' => 'string',
            'mediumtext' => 'text',

            'bool' => 'boolean',

            'timestamp' => 'datetime',
            default => $type,
        };
    }
}
