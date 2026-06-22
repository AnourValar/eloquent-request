<?php

namespace AnourValar\EloquentRequest\Tests;

use AnourValar\EloquentRequest\Tests\Models\Phone;
use AnourValar\EloquentRequest\Tests\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

abstract class AbstractSuite extends \Orchestra\Testbench\TestCase
{
    /**
     * Init
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpSchema();
        $this->setUpData();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            \AnourValar\EloquentRequest\Providers\EloquentRequestServiceProvider::class,
            \AnourValar\LaravelAtom\Providers\LaravelAtomServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'EloquentRequest' => \AnourValar\EloquentRequest\Facades\EloquentRequestFacade::class,
            'EloquentRequestFlat' => \AnourValar\EloquentRequest\Facades\EloquentRequestFlatFacade::class,
            'EloquentRequestSearch' => \AnourValar\EloquentRequest\Facades\EloquentRequestSearchFacade::class,
            'EloquentSerialize' => \AnourValar\EloquentRequest\Tests\Support\EloquentSerialize::class,
            'Atom' => \AnourValar\LaravelAtom\Facades\AtomFacade::class,
        ];
    }

    /**
     * Create the test schema
     *
     * @return void
     */
    protected function setUpSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('status')->default('active');
            $table->unsignedInteger('age')->nullable();
            $table->float('balance')->default(0);
            $table->boolean('is_admin')->default(false);
            $table->json('settings')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->string('type')->default('mobile');
        });
    }

    /**
     * Seed deterministic data
     *
     * @return void
     */
    protected function setUpData(): void
    {
        $users = [
            [
                'name' => 'Alice', 'email' => 'alice@example.com', 'status' => 'active', 'age' => 30,
                'balance' => 100.50, 'is_admin' => true, 'settings' => ['theme' => 'dark', 'tags' => ['vip', 'beta']],
                'email_verified_at' => '2021-01-01 00:00:00', 'created_at' => '2021-01-01 00:00:00',
            ],
            [
                'name' => 'Bob', 'email' => 'bob@example.com', 'status' => 'active', 'age' => 25,
                'balance' => 50.00, 'is_admin' => false, 'settings' => ['theme' => 'light', 'tags' => ['beta']],
                'email_verified_at' => null, 'created_at' => '2021-06-15 12:30:00',
            ],
            [
                'name' => 'Charlie', 'email' => null, 'status' => 'blocked', 'age' => 40,
                'balance' => 0, 'is_admin' => false, 'settings' => ['tags' => ['vip']],
                'email_verified_at' => '2022-03-03 10:00:00', 'created_at' => '2022-03-03 10:00:00',
            ],
            [
                'name' => 'Dave', 'email' => 'dave@example.com', 'status' => 'blocked', 'age' => null,
                'balance' => 7.77, 'is_admin' => false, 'settings' => null,
                'email_verified_at' => null, 'created_at' => '2023-09-09 09:09:09',
            ],
        ];

        foreach ($users as $attributes) {
            User::create($attributes);
        }

        Phone::insert([
            ['user_id' => 1, 'number' => '11111', 'type' => 'mobile'],
            ['user_id' => 1, 'number' => '12222', 'type' => 'home'],
            ['user_id' => 2, 'number' => '23333', 'type' => 'mobile'],
        ]);
    }

    /**
     * Build & resolve via the Service
     *
     * @param mixed $query
     * @param array $profile
     * @param array $request
     * @param mixed $buildRequest
     * @param callable|null $handler
     * @return mixed
     */
    protected function build($query, array $profile, array $request, &$buildRequest = null, ?callable $handler = null)
    {
        // Resolve the service directly (not via the facade) so that $buildRequest is passed by reference
        return \App::make(\AnourValar\EloquentRequest\Service::class)
            ->buildBy($query, $profile, $request, $buildRequest, $handler);
    }

    /**
     * Build & return the underlying query (NullAction), so SQL can be inspected
     *
     * @param mixed $query
     * @param array $profile
     * @param array $request
     * @param mixed $buildRequest
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildQuery($query, array $profile, array $request, &$buildRequest = null): \Illuminate\Database\Eloquent\Builder
    {
        $profile['options'][] = \AnourValar\EloquentRequest\Actions\NullAction::OPTION_APPLY;

        return $this->build($query, $profile, $request, $buildRequest);
    }
}
