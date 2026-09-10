<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestOptionAbbrModel;

it('still returns an abbr => name list via a deprecated shim that emits an E_USER_DEPRECATED notice', function () {
    TestOptionAbbrModel::create(['name' => 'Charlie', 'abbr' => 'C']);
    TestOptionAbbrModel::create(['name' => 'Alice', 'abbr' => 'A']);

    // Laravel's own error handler swallows E_USER_DEPRECATED into the log, so
    // catch it directly rather than via PHPUnit's expectUserDeprecationMessage().
    $deprecations = [];
    set_error_handler(function (int $errno, string $errstr) use (&$deprecations) {
        $deprecations[] = $errstr;

        return true;
    }, E_USER_DEPRECATED);

    try {
        $options = TestOptionAbbrModel::getAsOptions();
    } finally {
        restore_error_handler();
    }

    expect($options)->toBe([
        'A' => 'Alice',
        'C' => 'Charlie',
    ]);
    expect($deprecations)->not->toBeEmpty()
        ->each->toContain("Compose the GetAsOptions trait instead and call getAsOptions('abbr')");
});

it('forwards every argument -- positional and named -- through the variadic shim', function () {
    foreach (['Alice' => 'A', 'Bob' => 'B', 'Carol' => 'C'] as $name => $abbr) {
        TestOptionAbbrModel::create(['name' => $name, 'abbr' => $abbr]);
    }

    set_error_handler(fn () => true, E_USER_DEPRECATED);
    try {
        $page2 = TestOptionAbbrModel::getAsOptions('abbr', 'name', 2, 2);
        $filtered = TestOptionAbbrModel::getAsOptions('abbr', 'name', modifyQuery: fn ($q) => $q->where('abbr', '!=', 'B')->orderByDesc('name'));
    } finally {
        restore_error_handler();
    }

    expect($page2)->toBe(['C' => 'Carol']);
    expect($filtered)->toBe(['C' => 'Carol', 'A' => 'Alice']);
});
