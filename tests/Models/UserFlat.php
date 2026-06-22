<?php

namespace AnourValar\EloquentRequest\Tests\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $user_id
 * @property string $name
 * @property string|null $tag
 * @method static \Illuminate\Database\Eloquent\Builder orderBy($column, $direction = 'asc')
 * @method static int count($columns = '*')
 * @method static \Illuminate\Support\Collection pluck($column, $key = null)
 */
class UserFlat extends Model
{
    /**
     * @var string
     */
    protected $table = 'users_flat';

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * @var bool
     */
    public $timestamps = false;
}
