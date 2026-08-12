# Laravel Base

A collection of small Laravel traits and helpers shared across Coyote6 GraphX projects — mainly Eloquent model creation-time conventions (author/client/machine name/slug stamping), select-option helpers, and a couple of database/file utilities.

## Installation

```bash
composer require coyote6/laravel-base
```

The service provider (`Coyote6\LaravelBase\Providers\BaseServiceProvider`) is auto-discovered and merges this package's config under the `coyote6-base` key automatically. Publishing is optional, only needed to override the defaults:

```bash
php artisan vendor:publish --tag=coyote6-base-config
```

This publishes `config/coyote6-base.php` into your application.

## Directory Structure

- `src/Traits/Models/` — Traits meant to be `use`d directly on Eloquent models: `BootTraits`, `GetAsOptions`, `GetAsOptionsAbbr`, `GetBySlug`.
- `src/Traits/Models/Boot/` — Creation-helper traits that provide the `create*` hook methods `BootTraits` looks for via `method_exists()`: `Author`, `OriginalAuthor`, `Client`, `MachineName`, `MachineNameAsId`, `Slug`, and the shared internal helper `ResolvesMachineName`. Always used alongside `BootTraits` on the model, never alone.
- `src/Traits/Database/` — Traits meant to be `use`d on service providers, migrations, or other database-related classes: `DropsIndexes`, `ServiceProviderSeedsDb`.
- `src/Traits/Files/` — `ReadsCsv`, for reading a CSV file into an array.
- `src/Helpers/Helpers.php` — Global helper functions (`getCurrentUserId()`, `getCurrentUserClientId()`), autoloaded on every request via composer's `files` autoload.
- `src/Providers/BaseServiceProvider.php` — Merges and publishes `config/coyote6-base.php`.
- `config/coyote6-base.php` — Configuration consumed by the `Boot/*` traits (see Configuration below).

## Configuration

All config lives under the `coyote6-base` key. Every `field`/`reference` option is just the attribute name to read from or write to — override any of them per-application via the published config file.

| Key | Default | Used by |
|---|---|---|
| `machine_name.field` | `machine_name` | `MachineName` (destination attribute; `MachineNameAsId` always targets the primary key instead) |
| `machine_name.reference` | `name` | `MachineName`, `MachineNameAsId` (source attribute) |
| `machine_name.method` | `strictKebab` | `ResolvesMachineName` — which `Str::` method/macro generates the value |
| `machine_name.method_parameters` | `null` | `ResolvesMachineName` — extra arguments passed to `machine_name.method`, if any |
| `author.field` | `author_id` | `Author` |
| `original_author.field` | `original_author_id` | `OriginalAuthor` |
| `client.field` | `client_id` | `Client` (destination attribute) |
| `client.reference` | `client_id` | `Client`, `getCurrentUserClientId()` (attribute read off the current user) |
| `slug.field` | `slug` | `Slug` (destination attribute) |
| `slug.reference` | `name` | `Slug` (source attribute) |
| `slug.separator` | `-` | `Slug` — `Str::slug()`'s `$separator` |
| `slug.language` | `en` | `Slug` — `Str::slug()`'s `$language` |
| `slug.dictionary` | `['@' => 'at']` | `Slug` — `Str::slug()`'s `$dictionary` |

`machine_name.method` always runs through `Str::ascii()` first, regardless of which method is selected. Allowed values: `strictKebab` (default), `strictSnake`, `pureKebab`, `pureSnake`, `kebab`, `snake`, `dot`, `slug`, `studly`, `pascal`, `camel`, `lower`, `upper`, `deduplicate`, `transliterate` — see the config file's own comments for per-method examples and parameters (`machine_name.method_parameters`).

## Available Traits

### Boot Method

`Coyote6\LaravelBase\Traits\Models\BootTraits` — registers Eloquent model-event listeners (`creating`, `created`, `updating`, `updated`, `deleting`, `deleted`) that call a matching convention method on the model if it exists: `createAuthor`, `createOriginalAuthor`, `createClient`, `createMachineName`, `createSlug`, `createUuid` on `creating`; `modelCreating`/`modelCreated`/`modelUpdating`/`modelUpdated`/`modelDeleting`/`modelDeleted` at their respective events. A model opts into any of this just by defining the method — directly, or via one of the `Boot/*` traits below — `BootTraits` itself never requires any of them to exist.

For UUID primary keys, use Laravel's native `Illuminate\Database\Eloquent\Concerns\HasUuids` trait — this package doesn't ship its own `createUuid`, though the `BootTraits` hook for it is still there if you want to define one yourself.

### Creation Helpers (require `BootTraits`)

All under `Coyote6\LaravelBase\Traits\Models\Boot`:

- **`Author`** — sets `author.field` (default `author_id`) to the current user's id, unless already set (so an explicitly bulk-filled value, e.g. from an import, is preserved rather than overwritten).
- **`OriginalAuthor`** — sets `original_author.field` (default `original_author_id`) to the current user's id the same way, independently of `Author`. Meant as a permanent, foreign-key-free record of who created a row, for schemas where `author_id` has an `ON DELETE SET NULL` foreign key back to `users`.
- **`Client`** — sets `client.field` (default `client_id`) to the current user's `client.reference` attribute (default `client_id`), via `getCurrentUserClientId()`.
- **`MachineName`** — sets `machine_name.field` (default `machine_name`) from `machine_name.reference` (default `name`), via `ResolvesMachineName`.
- **`MachineNameAsId`** — same as `MachineName`, but writes to the model's primary key instead of a separate field. Use one or the other, never both — they share the `createMachineName` method name and will fatal on a trait collision if combined.
- **`Slug`** — sets `slug.field` (default `slug`) from `slug.reference` (default `name`) via `Str::slug()`, using `slug.separator`/`slug.language`/`slug.dictionary`.
- **`ResolvesMachineName`** — internal; composed by `MachineName` and `MachineNameAsId`, not meant to be `use`d directly on a model. Provides `resolveMachineName()` and `resolveMachineNameReference()`.

### Select Dropdown/Radio Button Helpers

Both under `Coyote6\LaravelBase\Traits\Models`, and both expose the same method name `getAsOptions()` — use one or the other, never both together on the same model:

- **`GetAsOptions`** — `id => name` option list, ordered by name.
- **`GetAsOptionsAbbr`** — `abbr => name` option list, ordered by name (e.g. states, countries).

Both cache their result in a static variable after the first call.

### Query Helpers

- **`GetBySlug`** (`Coyote6\LaravelBase\Traits\Models\GetBySlug`) — `static::getBySlug(string $slug)`, looks up a record by `slug.field` (default `slug`).

### Database Helpers

Both under `Coyote6\LaravelBase\Traits\Database`:

- **`DropsIndexes`** — `dropForeignIfExists(Blueprint $table, string $column, ?string $foreignKey = null)` and `dropIndexIfExists(Blueprint $table, string $column, ?string $foreignKey = null, bool $isForeign = false)`, for migrations that need to safely drop a foreign key/index that may or may not exist yet.
- **`ServiceProviderSeedsDb`** — `seedDbOnCommand($dir)`, called from a service provider's `boot()` to also run every seeder found in `$dir` whenever `db:seed` runs.

### File Helpers

- **`ReadsCsv`** (`Coyote6\LaravelBase\Traits\Files\ReadsCsv`) — `getItemsFromCSV(string $pathToFile): array`, reads a CSV file into an array of associative rows keyed by the header row.

### Global Helper Functions

Defined in `src/Helpers/Helpers.php`, autoloaded globally (no namespace):

- **`getCurrentUserId(): int|string|null`** — the authenticated user's id, or `null`.
- **`getCurrentUserClientId(): int|string|null`** — the authenticated user's `client.reference` attribute (default `client_id`), or `null`.

## Examples

### Author, Client, MachineName, and Slug together

`app/Models/Example.php`
```php
<?php

namespace App\Models;

use Coyote6\LaravelBase\Traits\Models\BootTraits;
use Coyote6\LaravelBase\Traits\Models\Boot\Author;
use Coyote6\LaravelBase\Traits\Models\Boot\Client;
use Coyote6\LaravelBase\Traits\Models\Boot\MachineName;
use Coyote6\LaravelBase\Traits\Models\Boot\Slug;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Example extends Model {

    use HasFactory,
        Author,
        Client,
        MachineName,
        Slug,
        BootTraits;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'author_id',
        'client_id',
        'machine_name',
        'slug',
    ];

}
```

On create, this fills `author_id` and `client_id` from the current user, and generates `machine_name`/`slug` from `name` — all only when not already set. `BootTraits` is what actually wires `createAuthor()`/`createClient()`/`createMachineName()`/`createSlug()` into Eloquent's `creating` event; it must be included alongside the others.

### OriginalAuthor

Add alongside `Author` for a permanent, foreign-key-free record of the original author (useful when `author_id` has an `ON DELETE SET NULL` foreign key and could later be nulled out):

```php
use Coyote6\LaravelBase\Traits\Models\Boot\Author;
use Coyote6\LaravelBase\Traits\Models\Boot\OriginalAuthor;

class Example extends Model {
    use Author, OriginalAuthor, BootTraits;
    // ...
    protected $fillable = ['id', 'author_id', 'original_author_id'];
}
```

### MachineNameAsId

When the machine name itself should be the primary key, use `MachineNameAsId` instead of `MachineName`:

```php
use Coyote6\LaravelBase\Traits\Models\Boot\MachineNameAsId;

class Example extends Model {
    use MachineNameAsId, BootTraits;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'name'];
}
```

### Overriding config

`config/coyote6-base.php` (after `php artisan vendor:publish --tag=coyote6-base-config`):
```php
'slug' => [
    'field' => 'slug',
    'reference' => 'title', // read from `title` instead of `name`
    'separator' => '-',
    'language' => 'en',
    'dictionary' => ['@' => 'at'],
],
```

### Get As Options

`app/Models/Example.php`
```php
<?php

namespace App\Models;

use Coyote6\LaravelBase\Traits\Models\GetAsOptions;

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

`app/Http/Controllers/ExampleController.php`
```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Example;

class ExampleController extends Controller {
    public function index () {
        dd (Example::getAsOptions());
    }
}
```

### Get As Options Abbreviation

Same usage as `GetAsOptions`, but keyed by `abbr` — note the method name is still `getAsOptions()`, not `getAsOptionsAbbr()`:

`app/Models/Example.php`
```php
<?php

namespace App\Models;

use Coyote6\LaravelBase\Traits\Models\GetAsOptionsAbbr;

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

```php
dd (Example::getAsOptions());
```

### Get By Slug

`app/Models/Example.php`
```php
<?php

namespace App\Models;

use Coyote6\LaravelBase\Traits\Models\GetBySlug;

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

`app/Http/Controllers/ExampleController.php`
```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Example;
use Illuminate\Http\Request;

class ExampleController extends Controller {
    public function index (Request $request) {
        $slug = $request->get('slug');
        dd (Example::getBySlug ($slug));
    }
}
```

### DropsIndexes

`database/migrations/2024_01_01_000000_example_migration.php`
```php
<?php

use Coyote6\LaravelBase\Traits\Database\DropsIndexes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    use DropsIndexes;

    public function up (): void {
        Schema::table('examples', function (Blueprint $table) {
            $this->dropForeignIfExists ($table, 'user_id');
            $table->dropColumn('user_id');
        });
    }

};
```

### ServiceProviderSeedsDb

`app/Providers/AppServiceProvider.php`
```php
<?php

namespace App\Providers;

use Coyote6\LaravelBase\Traits\Database\ServiceProviderSeedsDb;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {

    use ServiceProviderSeedsDb;

    public function boot (): void {
        $this->seedDbOnCommand (database_path('seeders/package'));
    }

}
```

Every seeder class found in that directory now also runs whenever `php artisan db:seed` runs.

### ReadsCsv

```php
<?php

namespace App\Console\Commands;

use Coyote6\LaravelBase\Traits\Files\ReadsCsv;
use Illuminate\Console\Command;

class ImportExamplesCommand extends Command {

    use ReadsCsv;

    protected $signature = 'examples:import {path}';

    public function handle (): void {
        $rows = $this->getItemsFromCSV ($this->argument('path'));

        foreach ($rows as $row) {
            // $row is an associative array keyed by the CSV's header row
        }
    }

}
```
