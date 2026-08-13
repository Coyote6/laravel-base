<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestModel;
use Coyote6\LaravelBase\Tests\Fixtures\TestUser;

it('fills author_id from the authenticated user when not already set', function () {
    $user = TestUser::create(['name' => 'Jane']);
    $this->actingAs($user);

    $model = TestModel::create(['name' => 'Example'])->refresh();

    expect($model->author_id)->toBe((string) $user->id);
});

it('leaves author_id null when there is no authenticated user', function () {
    $model = TestModel::create(['name' => 'Example'])->refresh();

    expect($model->author_id)->toBeNull();
});

it('does not overwrite an explicitly bulk-filled author_id', function () {
    $user = TestUser::create(['name' => 'Jane']);
    $this->actingAs($user);

    $model = TestModel::create([
        'name' => 'Example',
        'author_id' => 'imported-author-id',
    ])->refresh();

    expect($model->author_id)->toBe('imported-author-id');
});

it('respects a configured author.field', function () {
    config(['coyote6-base.author.field' => 'user_id']);

    $user = TestUser::create(['name' => 'Jane']);
    $this->actingAs($user);

    $model = TestModel::create(['name' => 'Example'])->refresh();

    expect($model->user_id)->toBe((string) $user->id)
        ->and($model->author_id)->toBeNull();
});
