<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestModel;
use Coyote6\LaravelBase\Tests\Fixtures\TestUser;

it('fills client_id from the authenticated user client_id when not already set', function () {
    $user = TestUser::create(['name' => 'Jane', 'client_id' => 'client-123']);
    $this->actingAs($user);

    $model = TestModel::create(['name' => 'Example'])->refresh();

    expect($model->client_id)->toBe('client-123');
});

it('leaves client_id null when there is no authenticated user', function () {
    $model = TestModel::create(['name' => 'Example'])->refresh();

    expect($model->client_id)->toBeNull();
});

it('does not overwrite an already-set client_id', function () {
    $user = TestUser::create(['name' => 'Jane', 'client_id' => 'client-123']);
    $this->actingAs($user);

    $model = TestModel::create([
        'name' => 'Example',
        'client_id' => 'imported-client-id',
    ])->refresh();

    expect($model->client_id)->toBe('imported-client-id');
});

it('respects a configured client.reference attribute on the user', function () {
    config(['coyote6-base.client.reference' => 'client_reference']);

    $user = TestUser::create([
        'name' => 'Jane',
        'client_id' => 'client-123',
        'client_reference' => 'client-456',
    ]);
    $this->actingAs($user);

    $model = TestModel::create(['name' => 'Example'])->refresh();

    expect($model->client_id)->toBe('client-456');
});

it('respects a configured client.field', function () {
    config(['coyote6-base.client.field' => 'tenant_id']);

    $user = TestUser::create(['name' => 'Jane', 'client_id' => 'client-123']);
    $this->actingAs($user);

    $model = TestModel::create(['name' => 'Example'])->refresh();

    expect($model->tenant_id)->toBe('client-123')
        ->and($model->client_id)->toBeNull();
});
