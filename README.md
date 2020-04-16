# Dynamic filters for QueryBuilder

## Installation

```bash
composer require anourvalar/eloquent-request
```


## Usage: filters

```php
class UserController extends Controller
{
    use \AnourValar\EloquentRequest\ControllerTrait;

    /**
     * Allows to filter by column 'created_at' using several operations
     *
     * @var array
     */
    protected $profile = [
        'filter' => [
            'created_at' => ['=', '!=', '<', '<=', '>', '>=', 'in', 'not-in'],
        ],
    ];

    /**
     * List of users
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function indexAny()
    {
        /**
         * Request example:
         * ['filter' => ['created_at' => ['>' => '2020-01-01']]]
         */

        $list = $this->buildBy(
            \App\User::orderBy('is_actual', 'DESC')
        );

        return view('admin.user.index', compact('users'));
    }
}

```


## Usage: sort

```php
class UserController extends Controller
{
    use \AnourValar\EloquentRequest\ControllerTrait;

    /**
     * Allows to sort using 'created_at' column
     *
     * @var array
     */
    protected $profile = [
        'sort' => ['created_at'],
    ];

    /**
     * List of users
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function indexAny()
    {
       /**
        * Request example:
        * ['sort' => ['created_at' => ['ASC']]]
        */

        $list = $this->buildBy(\App\User::class);

        return view('admin.user.index', compact('users'));
    }
}

```


## Usage: filters through relation

```php
class UserController extends Controller
{
    use \AnourValar\EloquentRequest\ControllerTrait;

    /**
     * Allows to filter by column 'phone_number' through relation 'userPhones'
     *
     * @var array
     */
    protected $profile = [
        'filter' => [
            'userPhones.phone_number' => ['like'],
        ],
    ];

    /**
     * List of users
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function indexAny()
    {
        /**
         * Request example:
         * ['filter' => ['userPhones.phone_number' => ['like' => '800']]]
         */

        $list = $this->buildBy(new \App\User);

        return view('admin.user.index', compact('users'));
    }
}

```


## Usage: simple pagination

```php
class UserController extends Controller
{
    use \AnourValar\EloquentRequest\ControllerTrait;

    /**
     * Simple pagination with limit of max available page (20)
     *
     * @var array
     */
    protected $profile = [
        'options' => [
            \AnourValar\EloquentRequest\Actions\PaginateAction::OPTION_SIMPLE_PAGINATE,
            \AnourValar\EloquentRequest\Actions\PaginateAction::OPTION_PAGE_MAX => 20,
        ],
    ];

    /**
     * List of users
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function indexAny()
    {
        /**
         * Request example:
         * ['page' => 5]
         */

        $list = $this->buildBy(
            \App\User::whereNotNull('email_verified_at') // query builder presets
        );

        return view('admin.user.index', compact('users'));
    }
}

```


## Usage: get all without pagination

```php
class UserController extends Controller
{
    use \AnourValar\EloquentRequest\ControllerTrait;

    /**
     * Get all items with limit (100)
     *
     * @var array
     */
    protected $profile = [
        'options' => [
            \AnourValar\EloquentRequest\Actions\GetAction::OPTION_APPLY,
            \AnourValar\EloquentRequest\Actions\GetAction::OPTION_LIMIT => 100,
        ],
    ];

    /**
     * List of users
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function indexAny()
    {
        $list = $this->buildBy('App\User');

        return view('admin.user.index', compact('users'));
    }
}

```


## Usage: model's scopes

```php
class UserController extends Controller
{
    use \AnourValar\EloquentRequest\ControllerTrait;

    /**
     * Allows to apply scope 'activeUsers' of model 'User'
     *
     * @var array
     */
    protected $profile = [
        'scope' => [
            'activeUsers',
        ],
    ];

    /**
     * List of users
     *
     * @return \Illuminate\View\View|\Illuminate\Contracts\View\Factory
     */
    public function indexAny()
    {
        /**
         * Request example:
         * ['scope' => ['activeUsers' => 1]]
         */

        $list = $this->buildBy(\App\User::class);

        return view('admin.user.index', compact('users'));
    }
}

```


## Via facade

```php
$profile = [
    'filter' => [
        'id' => ['in'],
    ],
];

$request = [
    'filter' => ['id' => ['in' => [1,2,3]]],
];

$collection = \EloquentRequest::buildBy(\App\User::class, $profile, $request);

```
