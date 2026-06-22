<?php

namespace AnourValar\EloquentRequest\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static bool insert(array $values)
 */
class Phone extends Model
{
    /**
     * @var string
     */
    protected $table = 'phones';

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $casts = [
        'number' => 'string',
        'type' => 'string',
    ];

    /**
     * User (belongsTo)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
