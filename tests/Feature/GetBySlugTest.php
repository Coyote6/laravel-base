<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestModel;

it('finds a record by its generated slug', function () {
    $model = TestModel::create(['name' => 'Example Name']);

    $found = TestModel::getBySlug('example-name');

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($model->id);
});

it('returns null for a slug that does not exist', function () {
    expect(TestModel::getBySlug('no-such-slug'))->toBeNull();
});

it('respects a configured slug.field', function () {
    config(['coyote6-base.slug.field' => 'title']);

    $model = TestModel::create(['name' => 'Example Name']);

    expect(TestModel::getBySlug('example-name')->id)->toBe($model->id);
});
