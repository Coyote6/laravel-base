<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestOptionAbbrModel;

it('returns an abbr => name option list ordered by name, cached after the first call', function () {
    $charlie = TestOptionAbbrModel::create(['name' => 'Charlie', 'abbr' => 'C']);
    $alice = TestOptionAbbrModel::create(['name' => 'Alice', 'abbr' => 'A']);

    $options = TestOptionAbbrModel::getAsOptions();

    expect($options)->toBe([
        'A' => 'Alice',
        'C' => 'Charlie',
    ]);

    // Created after the first call -- should not appear, since getAsOptions()
    // caches its result in a static variable per composing class.
    TestOptionAbbrModel::create(['name' => 'Bob', 'abbr' => 'B']);

    expect(TestOptionAbbrModel::getAsOptions())->toBe($options);
});
