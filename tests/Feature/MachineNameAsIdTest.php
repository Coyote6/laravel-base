<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestMachineNameAsIdModel;

it('generates the primary key from name using the default strictKebab method', function () {
    $model = TestMachineNameAsIdModel::create(['name' => 'Example Name']);

    expect($model->id)->toBe('example-name');
});

it('does not overwrite an already-set primary key', function () {
    $model = TestMachineNameAsIdModel::create([
        'id' => 'custom-id',
        'name' => 'Example Name',
    ]);

    expect($model->id)->toBe('custom-id');
});
