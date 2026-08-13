<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestDropsIndexesMigration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// DropsIndexes relies on MySQL-only syntax (information_schema.KEY_COLUMN_USAGE,
// SHOW INDEXES FROM) that has no sqlite equivalent -- the driver Testbench uses
// by default here. This only runs against a real MySQL/MariaDB connection.
//
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

    // Calling again should be a no-op, not throw, since the key is already gone.
    Schema::table('drops_indexes_children', function ($table) use ($migration) {
        $migration->dropForeignIfExists($table, 'parent_id');
    });

    expect(Schema::hasColumn('drops_indexes_children', 'parent_id'))->toBeTrue();
})->skip(
    fn () => ! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true),
    'requires a real MySQL/MariaDB connection'
);
