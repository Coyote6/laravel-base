<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestModel;

it('generates machine_name from name using the default strictKebab method', function () {
    $model = TestModel::create(['name' => 'Example Name'])->refresh();

    expect($model->machine_name)->toBe('example-name');
});

it('does not overwrite an already-set machine_name', function () {
    $model = TestModel::create([
        'name' => 'Example Name',
        'machine_name' => 'custom-machine-name',
    ])->refresh();

    expect($model->machine_name)->toBe('custom-machine-name');
});

it('respects a configured machine_name.method', function () {
    config(['coyote6-base.machine_name.method' => 'snake']);

    $model = TestModel::create(['name' => 'Example Name'])->refresh();

    expect($model->machine_name)->toBe('example_name');
});

it('respects a configured machine_name.reference', function () {
    config(['coyote6-base.machine_name.reference' => 'title']);

    $model = TestModel::create(['title' => 'From Title'])->refresh();

    expect($model->machine_name)->toBe('from-title');
});

it('respects a configured machine_name.field', function () {
    config(['coyote6-base.machine_name.field' => 'title']);

    $model = TestModel::create(['name' => 'Example Name'])->refresh();

    expect($model->title)->toBe('example-name')
        ->and($model->machine_name)->toBeNull();
});

it('transliterates non-ASCII characters via the forced Str::ascii() pass', function () {
    $model = TestModel::create(['name' => 'Café Münchën'])->refresh();

    expect($model->machine_name)->toBe('cafe-munchen');
});
