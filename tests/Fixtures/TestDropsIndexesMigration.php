<?php

namespace Coyote6\LaravelBase\Tests\Fixtures;

use Coyote6\LaravelBase\Traits\Database\DropsIndexes;
use Illuminate\Database\Migrations\Migration;

class TestDropsIndexesMigration extends Migration
{
    use DropsIndexes;
}
