<?php

namespace Coyote6\LaravelBase\Tests\Fixtures;

use Coyote6\LaravelBase\Traits\Database\DropsIndexes;
use Illuminate\Database\Migrations\Migration;

// A migration explicitly targeting the same connection TestCase already
// configures by name ('testing'), rather than relying on the null/default
// getConnection() every other fixture here uses -- proves
// DropsIndexes::schemaBuilder() actually threads getConnection() through to
// Schema::connection(), not just that the null/default path works.
class TestDropsIndexesOnNamedConnection extends Migration
{
    use DropsIndexes;

    protected $connection = 'testing';
}
