<?php

namespace Coyote6\LaravelBase\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

class TestUser extends Authenticatable
{
    protected $table = 'test_users';

    protected $fillable = [
        'name',
        'client_id',
        'client_reference',
    ];
}
