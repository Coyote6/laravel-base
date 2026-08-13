<?php

namespace Coyote6\LaravelBase\Tests\Fixtures;

use Coyote6\LaravelBase\Traits\Models\Boot\Author;
use Coyote6\LaravelBase\Traits\Models\Boot\Client;
use Coyote6\LaravelBase\Traits\Models\Boot\MachineName;
use Coyote6\LaravelBase\Traits\Models\Boot\OriginalAuthor;
use Coyote6\LaravelBase\Traits\Models\Boot\Slug;
use Coyote6\LaravelBase\Traits\Models\BootTraits;
use Coyote6\LaravelBase\Traits\Models\GetBySlug;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use Author;
    use OriginalAuthor;
    use Client;
    use MachineName;
    use Slug;
    use GetBySlug;
    use BootTraits;

    protected $table = 'test_models';

    protected $fillable = [
        'name',
        'title',
        'author_id',
        'original_author_id',
        'client_id',
        'machine_name',
        'slug',
    ];
}
