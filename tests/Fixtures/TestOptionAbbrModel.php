<?php

namespace Coyote6\LaravelBase\Tests\Fixtures;

use Coyote6\LaravelBase\Traits\Models\GetAsOptionsAbbr;
use Illuminate\Database\Eloquent\Model;

class TestOptionAbbrModel extends Model
{
    use GetAsOptionsAbbr;

    protected $table = 'test_option_abbr_models';

    protected $fillable = [
        'name',
        'abbr',
    ];
}
