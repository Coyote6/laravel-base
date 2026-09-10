<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestOptionModel;

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
