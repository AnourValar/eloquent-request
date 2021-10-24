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
    ];

    /**
     * Check if current structure of the flat table is actual
     *
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @return boolean
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

        Schema::create($flatInterface->model()->getTable(), function (Blueprint $table) use ($flatInterface)
        {
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
        Schema::dropIfExists($flatInterface->model()->getTable());
    }

    /**
     * Fill up flat table with all current data
     *
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @param string $model
     * @param callable $method
     * @return integer
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
        foreach ($model->cursor() as $item) {
            $method($flatInterface, $item);
            $affected++;
        }

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

        /*if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses($model)) && $model->trashed()) {
            $exists = false;
        }*/
        if (! $model->exists) {
            $exists = false;
        }
        if (! $data1) {
            throw new \LogicException('Incorrect usage.');
        }

        if ($exists) {
            $flatInterface->model()->updateOrCreate($data1, $data2);
        } else {
            $flatInterface->model()->where($data1)->delete();
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
        return $this->getProfile($flatInterface, $prefix, 'sort');
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
     * @param \AnourValar\EloquentRequest\FlatInterface $flatInterface
     * @return array
     */
    protected function getFactStructure(\AnourValar\EloquentRequest\FlatInterface $flatInterface): array
    {
        $structure = [];

        foreach (\DB::getSchemaBuilder()->getColumnListing($flatInterface->model()->getTable()) as $column) {
            $type = \DB::getSchemaBuilder()->getColumnType($flatInterface->model()->getTable(), $column);
            $type = $this->typeMapping[$type] ?? $type;

            $structure[] = ['column' => $column, 'type' => $type];
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

            $type = \Arr::last($blueprint->getColumns())->getAttributes()['type'];
            $type = $this->typeMapping[$type] ?? $type;

            $structure[] = ['column' => $column->target(), 'type' => $type];
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
            $result[$prefix . $column->target()] = $column->$method();
        }

        return array_filter($result);
    }
}