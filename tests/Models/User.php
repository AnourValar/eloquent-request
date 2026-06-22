<?php

namespace AnourValar\EloquentRequest\Tests\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static \Illuminate\Database\Eloquent\Builder where($column, $operator = null, $value = null)
 * @method static \AnourValar\EloquentRequest\Tests\Models\User create(array $attributes = [])
 * @method static \AnourValar\EloquentRequest\Tests\Models\User find($id, $columns = ['*'])
 */
class User extends Model
{
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'users';

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * @var array
     */
    protected $casts = [
        'name' => 'string',
        'status' => 'string',
        'age' => 'integer',
        'balance' => 'float',
        'is_admin' => 'boolean',
        'settings' => 'array',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Phones (hasMany)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function phones(): HasMany
    {
        return $this->hasMany(Phone::class);
    }

    /**
     * Scope: active
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $value
     * @return void
     */
    public function scopeActive(Builder $query, $value): void
    {
        if ($value) {
            $query->where('status', '=', 'active');
        } else {
            $query->where('status', '!=', 'active');
        }
    }

    /**
     * Scope: olderThan (with validation)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $age
     * @return void
     */
    public function scopeOlderThan(Builder $query, $age): void
    {
        validator(['age' => $age], ['age' => 'required|integer|min:0'])->validate();

        $query->where('age', '>=', (int) $age);
    }

    /**
     * Display names of the model attributes
     *
     * @return array
     */
    public function getAttributeNames(): array
    {
        return [
            'name' => 'User Name',
            'age' => 'User Age',
        ];
    }
}
