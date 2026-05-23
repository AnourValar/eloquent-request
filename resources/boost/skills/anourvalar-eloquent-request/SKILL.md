---
name: anourvalar-eloquent-request
description: Use when working with the anourvalar/eloquent-request Laravel package - building Eloquent query builders dynamically from validated HTTP request input (filters, relations, scopes, sort, pagination) via the EloquentRequest, EloquentRequestFlat, and EloquentRequestSearch facades, or when setting up flat/denormalized tables and search-string helpers.
---

# AnourValar Eloquent Request

`anourvalar/eloquent-request` turns user-supplied JSON request data into a safe Eloquent query. Access to columns and operations is gated by a per-controller "profile" array, validated through Laravel's validator. The package also ships helpers for flat/denormalized read tables (`FlatService`) and search-string normalization (`SearchService`).

## When to use

- Building list/index endpoints whose filters, sorts, or pagination come from the request payload.
- Implementing safe, profile-controlled filter operations (`=`, `!=`, `<`, `<=`, `>`, `>=`, `like`, `not-like`, `in`, `not-in`, `is-null`, `search`, `json-in`, `json-contains`, `json-not-in`, `json-not-contains`).
- Driving Eloquent `scope()`s, relation `whereHas`, custom casts/attributes, ranges, or simple/cursor/paginate actions from request data.
- Maintaining flat read-model tables synchronized with Eloquent models (with optional "shadow" deploy workflow).
- Generating normalized search strings (LIKE / FULLTEXT) and converting keyboard layout typos or visually similar Latin/Cyrillic letters.

## Facades

### `\AnourValar\EloquentRequest\Facades\EloquentRequestFacade` (alias `EloquentRequest`)

Resolves to `\AnourValar\EloquentRequest\Service` (singleton).

Public methods on the underlying service:

- `buildBy(mixed $query, array $profile, array $request, mixed &$buildRequest = null, ?callable $handler = null): mixed`
  - `$query` may be a model class string, a model instance, or an `Eloquent\Builder`.
  - `$profile` declares what is allowed (filter, relation, scope, sort, ranges, options, default_request, alias, custom_casts, custom_attributes, adapter).
  - `$request` is the raw input (e.g. `request()->input()`).
  - `&$buildRequest` is populated with a `\AnourValar\EloquentRequest\Helpers\Request` object that describes what was actually applied.
  - Optional `$handler(callable $runAction, Request $buildRequest)` lets you wrap execution (e.g. caching).
  - Returns whatever the matched action returns (paginator, collection, generator, null, etc.).
  - Throws `\Illuminate\Validation\ValidationException` or `\RuntimeException`.
- `extendActions(string $name, ?callable $action): self` — prepend (or unset when `null`) a custom action.
- `extendBuilders(string $name, ?callable $builder): self` — prepend (or unset) a custom builder.
- `replaceConfig(array $config): self` — recursively replace config keys (`per_page_key`, `page_key`, `cursor_key`, `filter_key`, `relation_key`, `scope_key`, `sort_key`, `filter_operations`, `validator`, etc.).

```php
use EloquentRequest;
use App\Models\User;

$profile = [
    'filter' => ['id' => ['in']],
    'sort'   => ['created_at'],
];

$collection = EloquentRequest::buildBy(User::class, $profile, request()->input());
```

### `\AnourValar\EloquentRequest\Facades\EloquentRequestFlatFacade` (alias `EloquentRequestFlat`)

Resolves to `\AnourValar\EloquentRequest\FlatService`.

Public methods:

- `isActualTable(FlatInterface $flatInterface): bool` — current DB schema matches `scheme()`.
- `createTable(FlatInterface $flatInterface): void` — drops & (re)creates the flat table.
- `dropTable(FlatInterface $flatInterface): void`
- `switchShadow(FlatInterface $flatInterface, bool $cleanUp = true): void` — promotes shadow table to the live name (use after deploy in the "shadow" workflow).
- `shadow(FlatInterface $flatInterface, bool $force = false): ?string` — returns the shadow table name when `config('eloquent_request.flat.shadow')` is true.
- `resync(FlatInterface $flatInterface, string $model, ?callable $closure = null, int $chunkSize = 5000): int` — backfill the flat table from a source model (uses `withTrashed()` when SoftDeletes is used).
- `sync(FlatInterface $flatInterface, Model $model): void` — refresh one model's flat rows (call from an observer).
- `syncSoft(FlatInterface $flatInterface, ?Model $model): void` — insert only if rows are missing.
- `getCasts(FlatInterface, string $prefix = ''): array`
- `getFilters(FlatInterface, string $prefix = ''): array`
- `getSorts(FlatInterface, string $prefix = ''): array`
- `getRanges(FlatInterface, string $prefix = ''): array`
- `getAttributeNames(FlatInterface, string $prefix = ''): array` — translated attribute display names.

```php
use EloquentRequestFlat;
use App\Drivers\OrderFlat;

// Inside an Order observer (saved/deleted):
EloquentRequestFlat::sync(app(OrderFlat::class), $order);

// In a migration up():
EloquentRequestFlat::createTable(app(OrderFlat::class));
```

### `\AnourValar\EloquentRequest\Facades\EloquentRequestSearchFacade` (alias `EloquentRequestSearch`)

Resolves to `\AnourValar\EloquentRequest\SearchService`.

Public methods:

- `typo(?string $value, string $inputLocale, string $outputLocale): ?string` — fix typos caused by wrong keyboard layout (locales: `en`, `ru` out of the box from `config('eloquent_request.typo')`).
- `similar(?string $value, string $inputLocale, string $outputLocale): ?string` — swap visually similar Latin/Cyrillic glyphs.
- `generate(?array $values, ?int $maxLength = null): ?string` — produces a space-padded, lowercased, deduplicated LIKE-friendly string (e.g. `' hello world '`). `$maxLength` must be >= 5.
- `generateFulltext(?string $phrase, array $typo = [], array $alias = []): ?string` — produces a FULLTEXT-friendly string. `$typo = ['from' => 'en', 'to' => 'ru']`; `$alias` maps tokens to canonical replacements.
- `prepare(string $value, bool $leftWildcard = true): string` — escape `%`, `_`, `\` and wrap with wildcards for a `WHERE column LIKE ?` clause.

```php
use EloquentRequestSearch;

$haystack = EloquentRequestSearch::generate([$user->name, $user->email]); // stored column
$needle   = EloquentRequestSearch::prepare(request('q'));                  // bind to LIKE
```

## Services

### `\AnourValar\EloquentRequest\Service`

The class behind `EloquentRequest`. Use it directly via `App::make(Service::class)` when you do not want the facade. Same `buildBy` / `extendActions` / `extendBuilders` / `replaceConfig` API.

### `\AnourValar\EloquentRequest\FlatService` and `\AnourValar\EloquentRequest\SearchService`

Described above — both can also be resolved out of the container directly.

### `\AnourValar\EloquentRequest\ControllerTrait`

Use this trait inside a Laravel controller to get a short `buildBy()` helper that reads the controller's `$profile` property (or `profile()` method) and the current `request()->input()` automatically.

```php
use AnourValar\EloquentRequest\ControllerTrait;
use AnourValar\EloquentRequest\Events\RequestBuiltEvent;
use AnourValar\EloquentRequest\Actions\PaginateAction;

class UserController extends Controller
{
    use ControllerTrait;

    protected $profile = [
        'filter' => [
            'created_at' => self::PROFILE_FILTER_DATE,         // preset from the trait
            'email'      => self::PROFILE_FILTER_TEXT,
        ],
        'ranges'  => ['created_at' => ['min' => '2018-01-01']],
        'scope'   => ['customStuff'],
        'sort'    => ['created_at'],
        'options' => [
            PaginateAction::OPTION_SIMPLE_PAGINATE,
            PaginateAction::OPTION_PAGE_MAX => 20,
        ],
    ];

    public function index()
    {
        $users = $this->buildBy(\App\Models\User::where('status', 'active'));
        $applied = $this->getBuildRequest(); // \AnourValar\EloquentRequest\Helpers\Request
    }
}
```

Filter presets exposed by the trait: `PROFILE_FILTER_ID`, `PROFILE_FILTER_BOOLEAN`, `PROFILE_FILTER_NUMBER`, `PROFILE_FILTER_DATE`, `PROFILE_FILTER_TEXT`, `PROFILE_FILTER_IS_NULL`, `PROFILE_FILTER_SEARCH`, `PROFILE_FILTER_JSON`.
Range presets: `PROFILE_RANGE_TINYINT`, `PROFILE_RANGE_UNSIGNED_TINYINT`, `PROFILE_RANGE_SMALLINT`, `PROFILE_RANGE_UNSIGNED_SMALLINT`, `PROFILE_RANGE_MEDIUMINT`, `PROFILE_RANGE_UNSIGNED_MEDIUMINT`, `PROFILE_RANGE_INT`, `PROFILE_RANGE_UNSIGNED_INT`.

### `\AnourValar\EloquentRequest\FlatInterface`

Implemented by your flat-table driver. Contract:

- `scheme(): \AnourValar\EloquentRequest\FlatMapper[]` — column definitions.
- `flatModel(): \Illuminate\Database\Eloquent\Model` — the Eloquent model bound to the flat table.
- `onTableCreated(): void` — hook for indexes/triggers.
- `multiple(\Illuminate\Database\Eloquent\Model $model): ?array` — emit multiple rows per source model (or `null`).
- `shouldBeStored(\Illuminate\Database\Eloquent\Model $model): bool` — exclude soft-deleted/inactive models.

### `\AnourValar\EloquentRequest\FlatMapper`

Constructed with an associative array. Public accessors: `source()`, `target()`, `purpose()`, `cast()`, `filter()`, `sort()`, `ranges()`, `attributeNames()`, `migration(Blueprint $table)`. Purpose constants: `FlatMapper::PURPOSE_IDENTIFIER`, `PURPOSE_PAYLOAD`, `PURPOSE_META`.

```php
use AnourValar\EloquentRequest\FlatMapper;
use Illuminate\Database\Schema\Blueprint;

new FlatMapper([
    'source'          => 'id',
    'target'          => 'order_id',
    'purpose'         => FlatMapper::PURPOSE_IDENTIFIER,
    'cast'            => 'integer',
    'filter'          => ['='],
    'sort'            => ['order_id'],
    'ranges'          => [],
    'attribute_names' => 'order id',
    'migration'       => fn (Blueprint $t) => $t->unsignedBigInteger('order_id')->index(),
]);
```

### `\AnourValar\EloquentRequest\Helpers\Request`

Returned via the `$buildRequest` out-parameter from `Service::buildBy()` (or via `ControllerTrait::getBuildRequest()`). Implements `ArrayAccess`. Useful methods: `get(?string $path)`, `filter(?string $path)`, `relation()`, `scope()`, `sort()`, `profile()`, `hasFilters()`, `hasSorts()`, `hasOnly(array $includeParams)`, `cacheKey()`, `getDisplayAttribute()`.

### Actions and their option constants

Built-in actions live under `\AnourValar\EloquentRequest\Actions\` and are toggled through the profile's `options` array (or `default_request`). Most relevant constants:

- `PaginateAction::OPTION_SIMPLE_PAGINATE`, `OPTION_PAGE_OVER_LAST_IS_FORBIDDEN`, `OPTION_PAGE_MAX`
- `CursorPaginateAction::OPTION_APPLY`
- `CursorAction::OPTION_APPLY`, `OPTION_LIMIT`
- `GetAction::OPTION_APPLY`, `OPTION_LIMIT`
- `GeneratorAction::OPTION_APPLY_CHUNK`, `OPTION_APPLY_CHUNK_ORDER_BY`, `OPTION_LIMIT`
- `NullAction::OPTION_APPLY`

Builder options:

- `FilterAndScopeBuilder::OPTION_GROUP_RELATION` — combine filters of the same relation into a single `whereHas` closure.
- `FilterAndScopeBuilder::OPTION_CASTS_NOT_REQUIRED`

Custom actions/builders can be registered via `extendActions()` / `extendBuilders()`, and `ActionInterface` / `Builders\BuilderInterface` are the contracts.

### Validators / Adapters

- `\AnourValar\EloquentRequest\Validators\ValidatorInterface` (default: `IlluminateValidator`) — swap via `replaceConfig(['validator' => ...])`.
- `\AnourValar\EloquentRequest\Adapters\AdapterInterface` (default: `CanonicalAdapter`) — set per-profile via `profile['adapter']`.

## Usage examples

### Profile-driven index endpoint

```php
use AnourValar\EloquentRequest\ControllerTrait;
use App\Models\User;

class UserController extends \App\Http\Controllers\Controller
{
    use ControllerTrait;

    protected $profile = [
        'filter' => [
            'created_at' => ['=', '!=', '<', '<=', '>', '>=', 'in', 'not-in'],
            'userPhones.phone_number' => ['like'], // relation field
        ],
        'sort' => ['created_at'],
    ];

    public function index()
    {
        return $this->buildBy(User::where('status', 'active'));
    }
}
```

Request body:

```json
{
  "filter": {
    "created_at": {">": "2021-01-01"},
    "userPhones.phone_number": {"like": "1234"}
  },
  "sort": {"created_at": "DESC"}
}
```

### Cache-aware handler

```php
use EloquentRequest;
use AnourValar\EloquentRequest\Helpers\Request;
use App\Models\Product;

$collection = EloquentRequest::buildBy(
    Product::query(),
    $profile,
    request()->input(),
    $buildRequest,
    function (callable $runAction, Request $buildRequest) {
        if ($buildRequest->hasOnly(['page'])) {
            return \Cache::remember('products:'.$buildRequest->get('page'), 3600, fn () => $runAction());
        }
        return $runAction();
    }
);
```

### Flat-table observer

```php
namespace App\Observers;

use App\Drivers\OrderFlat;
use App\Models\Order;
use EloquentRequestFlat;

class OrderObserver
{
    public function saved(Order $order): void
    {
        EloquentRequestFlat::sync(app(OrderFlat::class), $order);
    }

    public function deleted(Order $order): void
    {
        EloquentRequestFlat::sync(app(OrderFlat::class), $order);
    }
}
```

Shadow-deploy seeder:

```php
$driver = app(OrderFlat::class);

if (! EloquentRequestFlat::isActualTable($driver)) {
    EloquentRequestFlat::createTable($driver);
}

if (EloquentRequestFlat::shadow($driver)) {
    EloquentRequestFlat::resync($driver, \App\Models\Order::class, function ($driver, $model) {
        \DB::transaction(function () use ($driver, $model) {
            $this->syncSoft($driver, $model->fresh());
        });
    });

    \DB::transaction(fn () => EloquentRequestFlat::switchShadow($driver));
}
```

## Conventions / gotchas

- Auto-discovery: the package registers `EloquentRequestServiceProvider`, the three facade aliases, and an Artisan command `make:controller-buildby` that scaffolds a controller stub using `ControllerTrait`.
- `Service::buildBy()` requires `Eloquent\Builder` semantics — strings are instantiated, plain models are converted with `->newQuery()`. Passing a `\DB::table()` query throws `RuntimeException`.
- Request keys starting with `_` are stripped before processing (reserved namespace).
- Default profile shape (after `prepareProfile`): keys `filter`, `relation`, `scope`, `sort`, `alias`, `ranges`, `options`, `default_request`, `custom_casts`, `custom_attributes`, `custom_attributes_path`, `custom_attributes_handler`, `adapter`. `default_request` always seeds `per_page=20`, `page=1`, `cursor=null`.
- Default per-page hard cap is 2000 (`PaginateAction::MAX_PER_PAGE`). Use `PaginateAction::OPTION_PAGE_MAX` per profile to limit `page`.
- Filter operations are looked up in `config['filter_operations']` (keys above). Profiles must list the operations to allow per field — unknown or unauthorized operations are rejected by the validator.
- `FilterAndScopeBuilder::OPTION_GROUP_RELATION` is needed when multiple filters on the same relation must be combined inside a single `whereHas` closure.
- Validator failures surface as `\Illuminate\Validation\ValidationException`; if no action `passes()` the service throws `\RuntimeException('No actions are available for the request.')`.
- `SearchService::generate()` rejects `$maxLength < 5` with `RuntimeException`. Replacers and locale rules come from `config/eloquent_request.php` (`typo`, `similar`, `replacers`). Publish via `php artisan vendor:publish --tag=config`.
- Flat tables: when `config('eloquent_request.flat.shadow')` is `true`, write operations go to a `<table>_<sha1>` shadow table until `switchShadow()` is called. Set `shadow => false` once the schema stabilises.
- Translations: published with `php artisan vendor:publish` (vendor/eloquent-request). Available locales: `en`, `ru`.
- Requires PHP ^8.2 and Laravel 8.x – 13.x.
