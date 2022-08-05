<?php

namespace AnourValar\EloquentRequest;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FlatService
{
    /**
     * @var array
     */
    protected $typeMapping = [
        'bigint' => 'bigInteger',
        'jsonb' => 'json',
    ];

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
     * (Re)create flat table
     *
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @throws \LogicException
     * @return void
     */
    public function createTable(FlatInterface $flatInterface): void
    {
        $this->dropTable($flatInterface);

        Schema::create($flatInterface->flatModel()->getTable(), function (Blueprint $table) use ($flatInterface) {
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
     * Drop flat table
     *
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @return void
     */
    public function dropTable(FlatInterface $flatInterface): void
    {
        Schema::dropIfExists($flatInterface->flatModel()->getTable());
    }

    /**
     * Fill up flat table with all current data
     *
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @param string $model
     * @param callable $method
     * @return int
     */
    public function resync(FlatInterface $flatInterface, string $model, callable $method = null): int
    {
        if (! $method) {
            $method = [$this, 'sync'];
        }

        $model = new $model;
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses($model))) {
            $model = $model->withTrashed();
        }

        $affected = 0;
        $model->chunkById(5000, function ($items) use ($affected, $method, $flatInterface) {
            foreach ($items as $item) {
                $method($flatInterface, $item);
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
        $data1 = [];
        $data2 = [];
        $exists = false;

        foreach ($flatInterface->scheme() as $column) {
            $value = $model;
            foreach (explode('.', $column->source()) as $item) {
                $value = ( $value[$item] ?? null );
            }

            if ($column->isIdentifier()) {
                $data1[$column->target()] = $value;
            } else {
                $data2[$column->target()] = $value;

                if (isset($value)) {
                    $exists = true;
                }
            }
        }

        if (! $data1) {
            throw new \LogicException('Incorrect usage.');
        }

        if (! $model->exists || ! $flatInterface->shouldBeStored($model)) {
            $exists = false;
        }

        if ($exists) {
            $flatInterface->flatModel()->withCasts($this->getCasts($flatInterface))->updateOrCreate($data1, $data2);
        } else {
            $flatInterface->flatModel()->where($data1)->delete();
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
        $structure = [];

        foreach (\DB::getSchemaBuilder()->getColumnListing($flatInterface->flatModel()->getTable()) as $column) {
            $type = \DB::getSchemaBuilder()->getColumnType($flatInterface->flatModel()->getTable(), $column);
            $type = $this->typeMapping[$type] ?? $type;

            $length = \DB::connection()->getDoctrineColumn($flatInterface->flatModel()->getTable(), $column)->getLength();

            $structure[] = ['column' => $column, 'type' => $type, 'length' => $length];
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

        foreach ($flatInterface->scheme() as $column) {
            $blueprint = new \Illuminate\Database\Schema\Blueprint('');
            $column->migration($blueprint);
            $attribute = \Arr::last($blueprint->getColumns())->getAttributes();

            $type = $this->typeMapping[$attribute['type']] ?? $attribute['type'];
            $length = ($attribute['length'] ?? null);

            $structure[] = ['column' => $column->target(), 'type' => $type, 'length' => $length];
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
}
