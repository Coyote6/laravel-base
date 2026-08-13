<?php

namespace Coyote6\LaravelBase\Tests\Fixtures;

use Coyote6\LaravelBase\Traits\Models\GetAsOptions;
use Illuminate\Database\Eloquent\Model;

class TestOptionModel extends Model
{
    use GetAsOptions;

    protected $table = 'test_option_models';

    protected $fillable = [
        'name',
    ];
}
