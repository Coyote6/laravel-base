<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestUser;

it('getCurrentUserId returns the authenticated user id, or null when unauthenticated', function () {
    expect(getCurrentUserId())->toBeNull();

    $user = TestUser::create(['name' => 'Jane']);
    $this->actingAs($user);

    expect(getCurrentUserId())->toBe($user->id);
});

it('getCurrentUserClientId returns the client_id attribute, or null when unauthenticated', function () {
    expect(getCurrentUserClientId())->toBeNull();

    $user = TestUser::create(['name' => 'Jane', 'client_id' => 'client-123']);
    $this->actingAs($user);

    expect(getCurrentUserClientId())->toBe('client-123');
});

it('getCurrentUserClientId respects a configured client.reference', function () {
    config(['coyote6-base.client.reference' => 'client_reference']);

    $user = TestUser::create([
        'name' => 'Jane',
        'client_id' => 'client-123',
        'client_reference' => 'client-456',
    ]);
    $this->actingAs($user);

    expect(getCurrentUserClientId())->toBe('client-456');
});
