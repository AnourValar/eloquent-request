# QueryBuilder from Request
* Filling up the QueryBuilder from user data. Key feature is safety: all data is under a validation.
* Profile-based approach limit access to the columns and operations.

## Installation

```bash
composer require anourvalar/eloquent-request
```


## Usage: basic

**Request**

```json
{
  "filter": {
    "created_at": {">": "2021-01-01"}
  },
  "sort": {
    "created_at": "DESC"
  }
}

```

**Code**

```php
class UserController extends Controller
{
    /**
     * Profile
     */
    protected $profile = [
        'filter' => [
            'created_at' => ['=', '!=', '<', '<=', '>', '>=', 'in', 'not-in'],
        ],

        'sort' => ['created_at'],
    ];

    /**
     * Users list
     */
    public function index(Request $request)
    {
        $users = \App::make(\AnourValar\EloquentRequest\Service::class)->buildBy(
            \App\User::class,
            $this->profile,
            $request->input()
        );

        // Equals to:
        // \App\User
        //     ::where('created_at', '>', '2021-01-01')
        //     ->orderBy('created_at', 'DESC')
        //     ->paginate($request->input('page'));
    }
}

```


## Usage: relations & QueryBuilder preconfigure

**Request**

```json
{
  "filter": {
    "userPhones.phone_number": {"like": "1234"}
  }
}

```

**Code**

```php
class UserController extends Controller
{
    /**
     * Profile
     */
    protected $profile = [
        'filter' => [
            'userPhones.phone_number' => ['like'],
        ],
    ];

    /**
     * Users list
     */
    public function index(Request $request)
    {
        $users = \App::make(\AnourValar\EloquentRequest\Service::class)->buildBy(
            \App\User::where('status', '=', 'active'), $this->profile, $request->input()
        );

        // Equals to:
        // \App\User
        //    ::where('status', '=', 'active')
        //    ->whereHas('userPhones', function ($query)
        //    {
        //        $query->where('phone_number', 'like', '%1234%');
        //    })
        //    ->paginate($request->input('page'));
    }
}
```


## Usage: simple pagination

**Code**

```php
class UserController extends Controller
{
    /**
     * Profile
     */
    protected $profile = [
        'options' => [
            \AnourValar\EloquentRequest\Actions\PaginateAction::OPTION_SIMPLE_PAGINATE,
            \AnourValar\EloquentRequest\Actions\PaginateAction::OPTION_PAGE_MAX => 20,
        ],
    ];

    /**
     * Users list
     */
    public function indexAny()
    {
        $list = $this->buildBy(
            \App\User::whereNotNull('email_verified_at')
        );

        // Equals to: \App\User::whereNotNull('email_verified_at')->simplePaginate($request->input('page'));
    }
}

```


## Usage: advanced features

**Code**

```php
class UserController extends Controller
{
    use \AnourValar\EloquentRequest\ControllerTrait; // helper for quick usage

    /**
     * Profile
     */
    protected $profile = [
        'filter' => [
            'created_at' => \AnourValar\EloquentRequest\Events\RequestBuiltEvent::PROFILE_FILTER_DATE, // preset
        ],

        'ranges' => [
            'created_at' => ['min' => '2018-01-01'], // filter's constrainment
        ],

        'scope' => [
            'customStuff', // Eloquent scope
        ],

        'sort' => ['created_at'],
    ];

    /**
     * Users list
     */
    public function indexAny()
    {
        $users = $this->buildBy(\App\User::where('status', '=', 'active'));
    }
}

```


## Usage: via facade

**Code**

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


## Usage: Flat table

### Setup

Model Observer (saved, deleted):

```php
\EloquentRequestFlat::sync(\App::make(\App\Drivers\ModelFlat::class), $model);
```

Migration (up):

```php
\EloquentRequestFlat::createTable(\App::make(\App\Drivers\ModelFlat::class));
```

Migration (down):

```php
\EloquentRequestFlat::dropTable(\App::make(\App\Drivers\ModelFlat::class));
```

### "Simple" workflow

Config:

```php
'flat' => [
    'temporary' => false,
],
```

Seeder:

```php
if (! \EloquentRequestFlat::isActualTable($flatInterface)) {
    \EloquentRequestFlat::createTable($flatInterface);
    \EloquentRequestFlat::resync($flatInterface, \App\Model::class);
}
```

### "Temporary" workflow

Config:

```php
'flat' => [
    'temporary' => true, // it's recommended to false when the structure is permanent
],
```

Seeder:

```php
if (! \EloquentRequestFlat::isActualTable($flatInterface)) {
    \EloquentRequestFlat::createTable($flatInterface);
}
```

After deploy:

```php
if (\EloquentRequestFlat::temporary($flatInterface)) {
    $closure = function ($flatInterface, $model) {
        \DB::transaction(function () use ($flatInterface, $model) {
            // Atomic lock (for sync):
            // <...>
            $this->syncSoft($flatInterface, $model->fresh());
        });
    };
    \EloquentRequestFlat::resync($flatInterface, \App\Model::class, $closure);

    \DB::transaction(function () use ($flatInterface) {
        // Atomic lock (for sync):
        // <...>
        \EloquentRequestFlat::switchTemporary($flatInterface);
    });
}
```
