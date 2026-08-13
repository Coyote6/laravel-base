<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestOptionModel;

it('returns an id => name option list ordered by name, cached after the first call', function () {
    $charlie = TestOptionModel::create(['name' => 'Charlie']);
    $alice = TestOptionModel::create(['name' => 'Alice']);

    $options = TestOptionModel::getAsOptions();

    expect($options)->toBe([
        $alice->id => 'Alice',
        $charlie->id => 'Charlie',
    ]);

    // Created after the first call -- should not appear, since getAsOptions()
    // caches its result in a static variable per composing class.
    TestOptionModel::create(['name' => 'Bob']);

    expect(TestOptionModel::getAsOptions())->toBe($options);
});
