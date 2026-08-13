<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestReadsCsv;

it('reads a CSV file into an array of rows keyed by the header row', function () {
    $path = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($path, "name,email\nJane,jane@example.com\nJohn,john@example.com\n");

    $rows = (new TestReadsCsv())->readCsv($path);

    unlink($path);

    expect($rows)->toBe([
        1 => ['name' => 'Jane', 'email' => 'jane@example.com'],
        2 => ['name' => 'John', 'email' => 'john@example.com'],
    ]);
});
