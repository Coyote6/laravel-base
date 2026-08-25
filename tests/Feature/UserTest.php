<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestModel;
use Coyote6\LaravelBase\Tests\Fixtures\TestUser;

it('fills user_id from the authenticated user when not already set', function () {
    $user = TestUser::create(['name' => 'Jane']);
    $this->actingAs($user);

    $model = TestModel::create(['name' => 'Example'])->refresh();

    expect($model->user_id)->toBe((string) $user->id);
});

it('leaves user_id null when there is no authenticated user', function () {
    $model = TestModel::create(['name' => 'Example'])->refresh();

    expect($model->user_id)->toBeNull();
});

it('does not overwrite an explicitly bulk-filled user_id', function () {
    $user = TestUser::create(['name' => 'Jane']);
    $this->actingAs($user);

    $model = TestModel::create([
        'name' => 'Example',
        'user_id' => 'imported-user-id',
    ])->refresh();

    expect($model->user_id)->toBe('imported-user-id');
});

it('respects a configured user.field', function () {
    config(['coyote6-base.user.field' => 'assigned_to']);

    $user = TestUser::create(['name' => 'Jane']);
    $this->actingAs($user);

    $model = TestModel::create(['name' => 'Example'])->refresh();

    expect($model->assigned_to)->toBe((string) $user->id)
        ->and($model->user_id)->toBeNull();
});
