<?php

namespace Coyote6\LaravelBase\Tests\Fixtures;

use Coyote6\LaravelBase\Traits\Models\Boot\MachineNameAsId;
use Coyote6\LaravelBase\Traits\Models\BootTraits;
use Illuminate\Database\Eloquent\Model;

class TestMachineNameAsIdModel extends Model
{
    use MachineNameAsId;
    use BootTraits;

    protected $table = 'test_machine_name_as_id_models';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
    ];
}
