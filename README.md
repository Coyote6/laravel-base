# Laravel Base

This is just a collection of commonly used traits that are used throughout my sites and packages, such as UUIDs and autofilling author_id fields on models.

## To use
1. Select which traits you would like to implement.
2. Add that trait(s) to the model.

### Available Traits

#### Boot Method
BootTraits - Used to call the boot methods on creating, created, updating, updated, deleting, and deleted methods. Calls the parent method prior to implementing the other methods. Also adds static::getUser(), static::getUserId() methods to the find the current user in other traits.

#### Creation Helpers That Require The Boot Method
HasAuthor - Autofills a `author_id` property on the model using the current user's `id`.
HasClient - Autofills a `client_id` property on the model using the current user's `client_id` if the user has one.
HasMachineName - Autofills a `machine_name` property on the model using the `name` property, removing spaces and all punctuations.
HasUuid - Autofills the `id` property on the model with a uuid.

#### Select Dropdown/Radio Button Helpers
GetAsOptions - Uses the `id` and `name` model properties to create an array of options for select dropdowns and radio buttons.
GetAsOptionsAbbr - Uses the `abbr` and `name` model properties to create an array of options for select dropdowns and radio buttons. (Same as GetAsOptions, but uses an abbreviation field instead of the name. Mainly used for states and countries.)

#### Query Helpers
GetBySlug - Selects a model by a unique `slug` property.

## Examples

### Uuid Example
App\Models\Example.php
```php
<?php

namespace App\Models;

use Coyote6\LaravelBase\Traits\BootTraits;
use Coyote6\LaravelBase\Traits\HasUuid;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Example extends Model {

    use HasFactory, 
    	HasUuid,
    	BootTraits;
    
    public $incrementing = false;
	protected $keyType = 'string';

    protected $fillable = [
	    'id',
        'name',
    ];

}
```
This example just auto adds a uuid as the `id` if not filled in on creation.


### All Boot Traits Example
App\Models\Example.php
```php
<?php

namespace App\Models;

use Coyote6\LaravelBase\Traits\BootTraits;
use Coyote6\LaravelBase\Traits\HasAuthor;
use Coyote6\LaravelBase\Traits\HasClient;
use Coyote6\LaravelBase\Traits\HasMachineName;
use Coyote6\LaravelBase\Traits\HasUuid;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Example extends Model {

    use HasFactory, 
    	HasUuid,
    	HasAuthor,
    	HasClient
    	BootTraits;
    
    public $incrementing = false;
	protected $keyType = 'string';

    protected $fillable = [
	    'id',
	    'machine_name',
        'name',
        'client_id',
        'user_id'
    ];

}
```
This example auto adds a uuid as the `id`, the user\'s id and client id as `author_id` and `client_id` respectfully, and a machine `name_based` on the `name` if not filled in on creation.


### Get As Options Example
App\Models\Example.php
```php
<?php

namespace App\Models;

use Coyote6\LaravelBase\Traits\GetAsOptions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Example extends Model {

    use HasFactory, 
    	GetAsOptions;
    
    public $incrementing = false;
	protected $keyType = 'string';

    protected $fillable = [
	    'id',
        'name',
    ];

}
```
App\Http\Controllers\ExampleController.php
```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Example;

class ExampleController extends Controller {
	public function index () {
		dd (Example::getAsOptions());
	}
	
}

```

### Get As Options Abbreviation Example
App\Models\Example.php
```php
<?php

namespace App\Models;

use Coyote6\LaravelBase\Traits\GetAsOptions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Example extends Model {

    use HasFactory, 
    	GetAsOptionsAbbr;
    
    public $incrementing = false;
	protected $keyType = 'string';

    protected $fillable = [
	    'id',
	    'abbr',
        'name',
    ];

}
```
App\Http\Controllers\ExampleController.php
```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Example;

class ExampleController extends Controller {
	public function index () {
		dd (Example::getAsOptionsAbbr());
	}
	
}

```

### Get By Slug Example
App\Models\Example.php
```php
<?php

namespace App\Models;

use Coyote6\LaravelBase\Traits\GetBySlug;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Example extends Model {

    use HasFactory, 
    	GetBySlug;
    
    public $incrementing = false;
	protected $keyType = 'string';

    protected $fillable = [
	    'id',
	    'slug',
        'name',
    ];

}
```
App\Http\Controllers\ExampleController.php
```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Example;

class ExampleController extends Controller {
	public function index (Request $request) {
		$slug = $request->get('slug');
		dd (Example::getBySlug ($slug));
	}
	
}

```