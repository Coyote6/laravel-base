<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestModel;

it('throws for a machine_name.method not in the allowed list', function () {
    config(['coyote6-base.machine_name.method' => 'not_a_real_method']);

    TestModel::create(['name' => 'Example Name']);
})->throws(InvalidArgumentException::class);

it('rejects "ascii" itself as a machine_name.method', function () {
    config(['coyote6-base.machine_name.method' => 'ascii']);

    TestModel::create(['name' => 'Example Name']);
})->throws(InvalidArgumentException::class);

it('passes machine_name.method_parameters through to the configured method', function () {
    config([
        'coyote6-base.machine_name.method' => 'pureKebab',
        'coyote6-base.machine_name.method_parameters' => 2,
    ]);

    $model = TestModel::create(['name' => 'A - - B'])->refresh();

    expect($model->machine_name)->toBe('a--b');
});
