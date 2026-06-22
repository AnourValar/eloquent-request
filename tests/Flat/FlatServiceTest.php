<?php

namespace AnourValar\EloquentRequest\Tests\Flat;

use AnourValar\EloquentRequest\Tests\AbstractSuite;
use AnourValar\EloquentRequest\Tests\Models\User;
use AnourValar\EloquentRequest\Tests\Models\UserFlat;
use Illuminate\Support\Facades\Schema;

class FlatServiceTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function test_create_and_drop_table(): void
    {
        $mapper = new UserFlatMapper();
        $this->assertFalse(Schema::hasTable('users_flat'));

        \EloquentRequestFlat::createTable($mapper);

        $this->assertTrue(Schema::hasTable('users_flat'));
        $this->assertTrue($mapper->tableCreated);
        $this->assertSame(['user_id', 'name', 'tag'], Schema::getColumnListing('users_flat'));

        \EloquentRequestFlat::dropTable($mapper);
        $this->assertFalse(Schema::hasTable('users_flat'));
    }

    /**
     * @return void
     */
    public function test_is_actual_table(): void
    {
        $mapper = new UserFlatMapper();
        $this->assertFalse(\EloquentRequestFlat::isActualTable($mapper));

        \EloquentRequestFlat::createTable($mapper);

        $this->assertTrue(\EloquentRequestFlat::isActualTable($mapper));
    }

    /**
     * @return void
     */
    public function test_get_casts(): void
    {
        $mapper = new UserFlatMapper();
        $this->assertSame(['user_id' => 'integer', 'name' => 'string'], \EloquentRequestFlat::getCasts($mapper));
        $this->assertSame(['x_user_id' => 'integer', 'x_name' => 'string'], \EloquentRequestFlat::getCasts($mapper, 'x_'));
    }

    /**
     * @return void
     */
    public function test_get_filters(): void
    {
        $this->assertSame(
            ['user_id' => ['=', 'in'], 'name' => ['like'], 'tag' => ['=']],
            \EloquentRequestFlat::getFilters(new UserFlatMapper())
        );
    }

    /**
     * @return void
     */
    public function test_get_sorts(): void
    {
        $this->assertSame(['user_id', 'name'], \EloquentRequestFlat::getSorts(new UserFlatMapper()));
    }

    /**
     * @return void
     */
    public function test_get_ranges(): void
    {
        $this->assertSame(['tag' => ['max' => 100]], \EloquentRequestFlat::getRanges(new UserFlatMapper()));
    }

    /**
     * @return void
     */
    public function test_get_attribute_names(): void
    {
        $this->assertSame(['name' => 'Name'], \EloquentRequestFlat::getAttributeNames(new UserFlatMapper()));
    }

    /**
     * @return void
     */
    public function test_sync_single_row(): void
    {
        $mapper = new UserFlatMapper();
        \EloquentRequestFlat::createTable($mapper);

        \EloquentRequestFlat::sync($mapper, User::find(1));

        $rows = UserFlat::all();
        $this->assertCount(1, $rows);
        $this->assertSame(1, (int) $rows[0]->user_id);
        $this->assertSame('Alice', $rows[0]->name);
        $this->assertNull($rows[0]->tag);
    }

    /**
     * @return void
     */
    public function test_sync_multiple_rows(): void
    {
        $mapper = new UserFlatMapper();
        \EloquentRequestFlat::createTable($mapper);
        $mapper->multipleRows = [['tag' => 'red'], ['tag' => 'blue']];

        \EloquentRequestFlat::sync($mapper, User::find(1));

        $this->assertSame(['red', 'blue'], UserFlat::orderBy('id')->pluck('tag')->all());
    }

    /**
     * @return void
     */
    public function test_sync_replaces_previous_rows(): void
    {
        $mapper = new UserFlatMapper();
        \EloquentRequestFlat::createTable($mapper);

        $mapper->multipleRows = [['tag' => 'red'], ['tag' => 'blue']];
        \EloquentRequestFlat::sync($mapper, User::find(1));
        $this->assertCount(2, UserFlat::all());

        $mapper->multipleRows = [['tag' => 'green']];
        \EloquentRequestFlat::sync($mapper, User::find(1));
        $this->assertSame(['green'], UserFlat::pluck('tag')->all());
    }

    /**
     * @return void
     */
    public function test_sync_skips_when_should_not_be_stored(): void
    {
        $mapper = new UserFlatMapper();
        \EloquentRequestFlat::createTable($mapper);

        \EloquentRequestFlat::sync($mapper, User::find(1));
        $this->assertCount(1, UserFlat::all());

        $mapper->shouldStore = false;
        \EloquentRequestFlat::sync($mapper, User::find(1));
        $this->assertCount(0, UserFlat::all());
    }

    /**
     * @return void
     */
    public function test_sync_soft_inserts_once(): void
    {
        $mapper = new UserFlatMapper();
        \EloquentRequestFlat::createTable($mapper);

        \EloquentRequestFlat::syncSoft($mapper, User::find(1));
        $this->assertCount(1, UserFlat::all());

        // syncSoft must not duplicate an already-present model
        \EloquentRequestFlat::syncSoft($mapper, User::find(1));
        $this->assertCount(1, UserFlat::all());
    }

    /**
     * @return void
     */
    public function test_sync_soft_ignores_null_model(): void
    {
        $mapper = new UserFlatMapper();
        \EloquentRequestFlat::createTable($mapper);

        \EloquentRequestFlat::syncSoft($mapper, null);

        $this->assertCount(0, UserFlat::all());
    }

    /**
     * @return void
     */
    public function test_resync(): void
    {
        $mapper = new UserFlatMapper();
        \EloquentRequestFlat::createTable($mapper);

        $affected = \EloquentRequestFlat::resync($mapper, User::class);

        $this->assertSame(4, $affected);
        $this->assertSame(4, UserFlat::count());
        $this->assertSame(['Alice', 'Bob', 'Charlie', 'Dave'], UserFlat::orderBy('user_id')->pluck('name')->all());
    }

    /**
     * @return void
     */
    public function test_resync_with_custom_closure(): void
    {
        $mapper = new UserFlatMapper();
        \EloquentRequestFlat::createTable($mapper);

        $seen = [];
        $affected = \EloquentRequestFlat::resync($mapper, User::class, function ($flatInterface, $model) use (&$seen) {
            $seen[] = $model->name;
            $this->sync($flatInterface, $model);
        });

        $this->assertSame(4, $affected);
        $this->assertSame(['Alice', 'Bob', 'Charlie', 'Dave'], $seen);
        $this->assertSame(4, UserFlat::count());
    }

    /**
     * @return void
     */
    public function test_shadow_returns_null_when_disabled(): void
    {
        config(['eloquent_request.flat.shadow' => false]);
        $mapper = new UserFlatMapper();

        $this->assertNull(\EloquentRequestFlat::shadow($mapper));
        $this->assertNull(\EloquentRequestFlat::shadow($mapper, true));
    }

    /**
     * @return void
     */
    public function test_shadow_workflow(): void
    {
        config(['eloquent_request.flat.shadow' => true]);
        $mapper = new UserFlatMapper();

        // the (forced) shadow table name
        $shadowTable = \EloquentRequestFlat::shadow($mapper, true);
        $this->assertStringStartsWith('users_flat_', $shadowTable);

        // before creation there is no shadow table
        $this->assertNull(\EloquentRequestFlat::shadow($mapper));

        // createTable creates the shadow table
        \EloquentRequestFlat::createTable($mapper);
        $this->assertSame($shadowTable, \EloquentRequestFlat::shadow($mapper));
        $this->assertTrue(Schema::hasTable($shadowTable));
        $this->assertFalse(Schema::hasTable('users_flat'));

        // switchShadow renames the shadow into the real table
        \EloquentRequestFlat::switchShadow($mapper, false);
        $this->assertTrue(Schema::hasTable('users_flat'));
        $this->assertNull(\EloquentRequestFlat::shadow($mapper));
    }

    /**
     * @return void
     */
    public function test_switch_shadow_without_shadow_table_throws(): void
    {
        config(['eloquent_request.flat.shadow' => true]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Incorrect usage.');
        \EloquentRequestFlat::switchShadow(new UserFlatMapper(), false);
    }
}
