<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestDropsIndexesMigration;
use Coyote6\LaravelBase\Tests\Fixtures\TestDropsIndexesOnNamedConnection;
use Coyote6\LaravelBase\Tests\Fixtures\TestDropsIndexesWithoutConnection;
use Illuminate\Support\Facades\Schema;

// Now driven by Laravel's own driver-agnostic schema introspection
// (Schema\Builder::getForeignKeys()/getIndexes()/getTables()), not raw
// MySQL-only SQL -- runs unskipped on every driver, including the sqlite
// default here (was previously only ever exercised against a real
// MySQL/MariaDB connection).
it('drops a foreign key and its backing index if they exist, and is a no-op if not', function () {
    Schema::create('drops_indexes_parents', function ($table) {
        $table->id();
    });

    Schema::create('drops_indexes_children', function ($table) {
        $table->id();
        $table->foreignId('parent_id')->constrained('drops_indexes_parents');
    });

    $migration = new TestDropsIndexesMigration();

    Schema::table('drops_indexes_children', function ($table) use ($migration) {
        $migration->dropForeignIfExists($table, 'parent_id');
    });

    expect(Schema::getForeignKeys('drops_indexes_children'))->toBe([]);

    // Calling again should be a no-op, not throw, since the key is already gone.
    Schema::table('drops_indexes_children', function ($table) use ($migration) {
        $migration->dropForeignIfExists($table, 'parent_id');
    });

    expect(Schema::hasColumn('drops_indexes_children', 'parent_id'))->toBeTrue();
});

it('drops a foreign key by its explicit constraint name', function () {
    Schema::create('drops_indexes_parents', function ($table) {
        $table->id();
    });

    Schema::create('drops_indexes_children', function ($table) {
        $table->id();
        $table->unsignedBigInteger('parent_id');
        $table->foreign('parent_id', 'custom_fk_name')->references('id')->on('drops_indexes_parents');
    });

    $migration = new TestDropsIndexesMigration();

    Schema::table('drops_indexes_children', function ($table) use ($migration) {
        $migration->dropForeignIfExists($table, 'parent_id', 'custom_fk_name');
    });

    expect(Schema::getForeignKeys('drops_indexes_children'))->toBe([]);
});

it('foreignKeysReferencing finds every table pointing at the given one, across the whole connection', function () {
    Schema::create('drops_indexes_parents', function ($table) {
        $table->id();
    });

    Schema::create('drops_indexes_children', function ($table) {
        $table->id();
        $table->foreignId('parent_id')->constrained('drops_indexes_parents');
    });

    Schema::create('drops_indexes_grandchildren', function ($table) {
        $table->id();
        $table->foreignId('parent_id')->constrained('drops_indexes_parents');
    });

    // Never referenced by anything -- proves this isn't just "every table".
    Schema::create('drops_indexes_unrelated', function ($table) {
        $table->id();
    });

    $migration = new TestDropsIndexesMigration();
    $referencing = $migration->foreignKeysReferencing('drops_indexes_parents');

    expect(collect($referencing)->pluck('referencing_table')->sort()->values()->all())
        ->toBe(['drops_indexes_children', 'drops_indexes_grandchildren']);
});

it('foreignKeysReferencing returns nothing for a table nothing points at', function () {
    Schema::create('drops_indexes_unrelated', function ($table) {
        $table->id();
    });

    $migration = new TestDropsIndexesMigration();

    expect($migration->foreignKeysReferencing('drops_indexes_unrelated'))->toBe([]);
});

it('schemaBuilder resolves via the composing migration\'s own named connection', function () {
    Schema::create('drops_indexes_parents', function ($table) {
        $table->id();
    });

    Schema::create('drops_indexes_children', function ($table) {
        $table->id();
        $table->foreignId('parent_id')->constrained('drops_indexes_parents');
    });

    // TestDropsIndexesOnNamedConnection declares $connection = 'testing'
    // explicitly, rather than relying on the null/default every other
    // fixture here uses -- proves getConnection() is actually threaded
    // through to Schema::connection(), not just that the default works.
    $migration = new TestDropsIndexesOnNamedConnection();

    Schema::table('drops_indexes_children', function ($table) use ($migration) {
        $migration->dropForeignIfExists($table, 'parent_id');
    });

    expect(Schema::getForeignKeys('drops_indexes_children'))->toBe([]);
});

it('schemaBuilder falls back to the default connection for a composing class with no getConnection()', function () {
    Schema::create('drops_indexes_parents', function ($table) {
        $table->id();
    });

    Schema::create('drops_indexes_children', function ($table) {
        $table->id();
        $table->foreignId('parent_id')->constrained('drops_indexes_parents');
    });

    $notAMigration = new TestDropsIndexesWithoutConnection();

    Schema::table('drops_indexes_children', function ($table) use ($notAMigration) {
        $notAMigration->dropForeignIfExists($table, 'parent_id');
    });

    expect(Schema::getForeignKeys('drops_indexes_children'))->toBe([]);
});
