<?php

namespace Coyote6\LaravelBase\Tests\Fixtures;

use Coyote6\LaravelBase\Traits\Database\DropsIndexes;

// Not a Migration -- has no getConnection() of its own, so
// DropsIndexes::schemaBuilder() has to fall back to the default
// connection's Schema Builder instead of assuming every composing class is
// a Migration.
class TestDropsIndexesWithoutConnection
{
    use DropsIndexes;
}
