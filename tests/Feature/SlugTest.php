<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestModel;

it('generates a slug from name via Str::slug()', function () {
    $model = TestModel::create(['name' => 'Example Name'])->refresh();

    expect($model->slug)->toBe('example-name');
});

it('does not overwrite an already-set slug', function () {
    $model = TestModel::create([
        'name' => 'Example Name',
        'slug' => 'custom-slug',
    ])->refresh();

    expect($model->slug)->toBe('custom-slug');
});

it('respects a configured slug.separator', function () {
    config(['coyote6-base.slug.separator' => '_']);

    $model = TestModel::create(['name' => 'Example Name'])->refresh();

    expect($model->slug)->toBe('example_name');
});

it('respects a configured slug.dictionary', function () {
    config(['coyote6-base.slug.dictionary' => ['@' => 'at']]);

    $model = TestModel::create(['name' => 'user@example'])->refresh();

    expect($model->slug)->toBe('user-at-example');
});

it('respects a configured slug.reference', function () {
    config(['coyote6-base.slug.reference' => 'title']);

    $model = TestModel::create(['name' => 'Example Name', 'title' => 'From Title'])->refresh();

    expect($model->slug)->toBe('from-title');
});

it('respects a configured slug.field', function () {
    config(['coyote6-base.slug.field' => 'title']);

    $model = TestModel::create(['name' => 'Example Name'])->refresh();

    expect($model->title)->toBe('example-name')
        ->and($model->slug)->toBeNull();
});
