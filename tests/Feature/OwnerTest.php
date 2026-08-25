<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestModel;
use Coyote6\LaravelBase\Tests\Fixtures\TestUser;

it('fills owner_id from the authenticated user when not already set', function () {
    $user = TestUser::create(['name' => 'Jane']);
    $this->actingAs($user);

    $model = TestModel::create(['name' => 'Example'])->refresh();

    expect($model->owner_id)->toBe((string) $user->id);
});

it('leaves owner_id null when there is no authenticated user', function () {
    $model = TestModel::create(['name' => 'Example'])->refresh();

    expect($model->owner_id)->toBeNull();
});

it('does not overwrite an explicitly bulk-filled owner_id', function () {
    $user = TestUser::create(['name' => 'Jane']);
    $this->actingAs($user);

    $model = TestModel::create([
        'name' => 'Example',
        'owner_id' => 'imported-owner-id',
    ])->refresh();

    expect($model->owner_id)->toBe('imported-owner-id');
});

it('respects a configured owner.field', function () {
    config(['coyote6-base.owner.field' => 'created_by']);

    $user = TestUser::create(['name' => 'Jane']);
    $this->actingAs($user);

    $model = TestModel::create(['name' => 'Example'])->refresh();

    expect($model->created_by)->toBe((string) $user->id)
        ->and($model->owner_id)->toBeNull();
});
