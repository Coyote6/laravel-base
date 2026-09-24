<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestOptionModel;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

// String concatenation syntax is dialect-specific -- this is the same choice
// a real Expression $field caller has to make (see GetAsOptions' own
// docblock). Testbench defaults to sqlite here (see DropsIndexesTest for the
// same note), but branching on the actual driver keeps these tests honest
// about -- and correct against -- every connection this trait ships support
// for, not just the one CI happens to run.
function concatCodeAndName(): Expression
{
    return match (DB::connection()->getDriverName()) {
        'sqlite', 'pgsql' => DB::raw("(code || ' - ' || name) AS label"),
        'sqlsrv' => DB::raw("(code + ' - ' + name) AS label"),
        default => DB::raw("CONCAT(code, ' - ', name) AS label"), // mysql, mariadb
    };
}

it('returns an id => name option list ordered by name', function () {
    $charlie = TestOptionModel::create(['name' => 'Charlie']);
    $alice = TestOptionModel::create(['name' => 'Alice']);

    expect(TestOptionModel::getAsOptions())->toBe([
        $alice->id => 'Alice',
        $charlie->id => 'Charlie',
    ]);
});

it('is not cached -- every call reflects the current data', function () {
    $alice = TestOptionModel::create(['name' => 'Alice']);

    expect(TestOptionModel::getAsOptions())->toBe([$alice->id => 'Alice']);

    $bob = TestOptionModel::create(['name' => 'Bob']);

    expect(TestOptionModel::getAsOptions())->toBe([
        $alice->id => 'Alice',
        $bob->id => 'Bob',
    ]);
});

it('keys options by $key and labels them by $field', function () {
    $alice = TestOptionModel::create(['name' => 'Alice']);
    $bob = TestOptionModel::create(['name' => 'Bob']);

    expect(TestOptionModel::getAsOptions('name', 'id'))->toBe([
        'Alice' => $alice->id,
        'Bob' => $bob->id,
    ]);
});

it('paginates with $limit and $page, ordered by $field', function () {
    foreach (['Alice', 'Bob', 'Carol', 'Dave'] as $name) {
        TestOptionModel::create(['name' => $name]);
    }

    expect(array_values(TestOptionModel::getAsOptions('name', 'name', 2, 1)))
        ->toBe(['Alice', 'Bob']);
    expect(array_values(TestOptionModel::getAsOptions('name', 'name', 2, 2)))
        ->toBe(['Carol', 'Dave']);
});

it('treats $limit = 0 as no limit and clamps $page below 1', function () {
    foreach (['Alice', 'Bob', 'Carol'] as $name) {
        TestOptionModel::create(['name' => $name]);
    }

    expect(TestOptionModel::getAsOptions('name', 'name', 0))->toHaveCount(3);
    expect(TestOptionModel::getAsOptions('name', 'name', 2, 0))->toHaveCount(2);
});

it('re-orders the list with the $modifyQuery closure, composing with $limit and $page', function () {
    $alice = TestOptionModel::create(['name' => 'Alice']);
    $bob = TestOptionModel::create(['name' => 'Bob']);
    $carol = TestOptionModel::create(['name' => 'Carol']);

    // A direction the plain $field sort can't express.
    expect(array_values(TestOptionModel::getAsOptions(modifyQuery: fn ($q) => $q->orderByDesc('name'))))
        ->toBe(['Carol', 'Bob', 'Alice']);

    // A column that is neither $key nor $field.
    expect(TestOptionModel::getAsOptions('id', 'name', modifyQuery: fn ($q) => $q->orderByDesc('id')))
        ->toBe([
            $carol->id => 'Carol',
            $bob->id => 'Bob',
            $alice->id => 'Alice',
        ]);

    // Still paginates on top of the custom order.
    expect(array_values(TestOptionModel::getAsOptions('id', 'name', 2, 1, fn ($q) => $q->orderByDesc('name'))))
        ->toBe(['Carol', 'Bob']);
});

it('filters the list with a where clause in the $modifyQuery closure', function () {
    $alice = TestOptionModel::create(['name' => 'Alice']);
    $bob = TestOptionModel::create(['name' => 'Bob']);
    $carol = TestOptionModel::create(['name' => 'Carol']);

    // A closure that filters must re-add its own ordering.
    expect(TestOptionModel::getAsOptions(modifyQuery: fn ($q) => $q->whereIn('name', ['Alice', 'Carol'])->orderBy('name')))
        ->toBe([
            $alice->id => 'Alice',
            $carol->id => 'Carol',
        ]);

    // Filter + pagination compose: exclude Alice, then page 2 of 1-per-page.
    expect(TestOptionModel::getAsOptions('id', 'name', 1, 2, fn ($q) => $q->where('id', '>', $alice->id)->orderBy('id')))
        ->toBe([$carol->id => 'Carol']);
});

it('accepts an Expression $field for a DB-computed label', function () {
    $alice = TestOptionModel::create(['name' => 'Alice', 'code' => 'A']);
    $bob = TestOptionModel::create(['name' => 'Bob', 'code' => 'B']);

    $options = TestOptionModel::getAsOptions(
        field: concatCodeAndName(),
        modifyQuery: fn ($q) => $q->orderBy('name'),
    );

    expect($options)->toBe([
        $alice->id => 'A - Alice',
        $bob->id => 'B - Bob',
    ]);
});

it('does not apply the default $field ASC sort when $field is an Expression', function () {
    // Without modifyQuery there is nothing to order by -- an aliased
    // Expression can't be reused inside ORDER BY -- so the caller supplies
    // ordering explicitly. Proven here by two different orderings against
    // the same Expression, both honored.
    TestOptionModel::create(['name' => 'Alice', 'code' => 'A']);
    TestOptionModel::create(['name' => 'Bob', 'code' => 'B']);

    $ascending = TestOptionModel::getAsOptions(
        field: concatCodeAndName(),
        modifyQuery: fn ($q) => $q->orderBy('name', 'asc'),
    );
    $descending = TestOptionModel::getAsOptions(
        field: concatCodeAndName(),
        modifyQuery: fn ($q) => $q->orderBy('name', 'desc'),
    );

    expect(array_values($ascending))->toBe(['A - Alice', 'B - Bob'])
        ->and(array_values($descending))->toBe(['B - Bob', 'A - Alice']);
});

it('accepts a Closure $field for a PHP-computed label', function () {
    $alice = TestOptionModel::create(['name' => 'Alice', 'code' => 'A']);
    $bob = TestOptionModel::create(['name' => 'Bob', 'code' => 'B']);

    $options = TestOptionModel::getAsOptions(
        field: fn (TestOptionModel $model) => "{$model->code} - {$model->name}",
        modifyQuery: fn ($q) => $q->orderBy('name'),
    );

    expect($options)->toBe([
        $alice->id => 'A - Alice',
        $bob->id => 'B - Bob',
    ]);
});

it('paginates a Closure $field the same way as a plain column', function () {
    foreach (['Alice', 'Bob', 'Carol', 'Dave'] as $name) {
        TestOptionModel::create(['name' => $name]);
    }

    $options = TestOptionModel::getAsOptions(
        field: fn (TestOptionModel $model) => $model->name,
        limit: 2,
        page: 2,
        modifyQuery: fn ($q) => $q->orderBy('name'),
    );

    expect(array_values($options))->toBe(['Carol', 'Dave']);
});
