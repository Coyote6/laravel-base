<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestModel;
use Coyote6\LaravelBase\Tests\Fixtures\TestUser;

it('fills original_author_id from the authenticated user, independently of author_id', function () {
    $user = TestUser::create(['name' => 'Jane']);
    $this->actingAs($user);

    $model = TestModel::create(['name' => 'Example'])->refresh();

    expect($model->original_author_id)->toBe((string) $user->id)
        ->and($model->author_id)->toBe((string) $user->id);
});

it('does not overwrite an explicitly bulk-filled original_author_id', function () {
    $user = TestUser::create(['name' => 'Jane']);
    $this->actingAs($user);

    $model = TestModel::create([
        'name' => 'Example',
        'author_id' => 'imported-author-id',
        'original_author_id' => 'imported-author-id',
    ])->refresh();

    expect($model->original_author_id)->toBe('imported-author-id')
        ->and($model->author_id)->toBe('imported-author-id');
});

it('respects a configured original_author.field', function () {
    config(['coyote6-base.original_author.field' => 'owner']);

    $user = TestUser::create(['name' => 'Jane']);
    $this->actingAs($user);

    $model = TestModel::create(['name' => 'Example'])->refresh();

    expect($model->owner)->toBe((string) $user->id)
        ->and($model->original_author_id)->toBeNull();
});
