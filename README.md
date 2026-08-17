# Laravel Base

A collection of small Laravel traits and helpers — mainly Eloquent model creation-time conventions (author/client/machine name/slug stamping), select-option helpers, and a couple of database/file utilities.

## Installation

```bash
composer require coyote6/laravel-base
```

The service provider (`Coyote6\LaravelBase\Providers\BaseServiceProvider`) is auto-discovered and merges this package's config under the `coyote6-base` key automatically. Publishing is optional, only needed to override the defaults:

```bash
php artisan vendor:publish --tag=coyote6-base-config
```

This publishes `config/coyote6-base.php` into your application.

## Upgrade From 0.2.7

v0.3.0 is a breaking release. See `CHANGELOG.md` for the full old-namespace →
new-namespace table — every trait moved, and four were renamed
(`HasAuthor`/`HasClient`/`HasMachineName`/`HasMachineNameAsId` →
`Author`/`Client`/`MachineName`/`MachineNameAsId`). There's no deprecation
shim; it's a hard cutover.

Those four new names are short and generic enough to routinely collide with
a real domain class already living in an app being upgraded — `Client` and
`Author` are common model names. So the upgrade command below always
imports them aliased (`BootAuthor`/`BootClient`/`BootMachineName`/
`BootMachineNameAsId`) rather than bare, regardless of whether this
particular file happens to collide today. This is specific to the automated
upgrade path; a fresh install has no such history to protect against, so
Creation Helpers below documents the plain, unaliased names as the default —
alias by hand only if your own app also has a class by the same name.

**Automating the namespace rename.** This package ships a command that scans
your application and rewrites old trait references to whatever the current
version needs:

```bash
php artisan coyote6-base:upgrade
```

It scans `app/` and `database/` by default (comma-separate `--path` to scan
somewhere else, e.g. `--path=app,database,routes`), then runs every upgrade
step it knows about, one at a time — currently just the v0.2.7 → v0.3.0
rename below, but future breaking releases will add their own step here, so
running this command again after a later update only applies whatever's
actually new. Each step reports on its own:

```
Running v0.3.0 upgrades
Found 12 files that would change. Apply the changes? (yes/no) [no]:
```

Answer `yes` to write that step's changes, or `no` to leave them untouched
and move on to the next step. Pass `--apply` to skip the prompt and write
immediately instead — useful in CI or any other non-interactive context:

```bash
php artisan coyote6-base:upgrade --apply
```

A step with nothing left to do prints `No file changes found.` and moves on
without asking anything, so it's safe to re-run this command at any time,
even after it's already been applied.

It rewrites both the `use` import statement and the bare `use ShortName;`
trait-inclusion line inside a class body, for every renamed/moved trait —
including an aliased import (`use Coyote6\LaravelBase\Traits\HasAuthor as
Whatever;`), which keeps your alias and only swaps the underlying
namespace. `HasAuthor`/`HasClient`/`HasMachineName`/`HasMachineNameAsId`
are always rewritten aliased as `BootAuthor`/`BootClient`/
`BootMachineName`/`BootMachineNameAsId`, per the naming note above, even in
files where the bare name wouldn't currently collide with anything — so
the same trait always reads the same way everywhere in your codebase, and
you never have to wonder why one file says `Client` and another says
`BootClient`.

Two situations it can't safely handle automatically:

- **Even the `Boot*` alias collides with an existing, unrelated class
  already resolvable in that file** — either because the file already has
  its own `use App\Models\BootClient;`, or because the file lives in the
  same namespace as a `BootClient` class and would resolve it there with
  no import at all. To prevent asking on every model, the command asks once
  per distinct trait/collision (not once per file) as the same collision 
  typically repeats across every model using that trait in the same namespace:

  ```
  BootClient (HasClient's replacement) collides with an existing class in 4 files. Please provide a new alias for the trait, or leave blank to skip and manually review:
  ```

  Answer with a different alias to apply it everywhere that collision
  occurs, or leave it blank to skip those files and list them under a
  "collide with an existing, unrelated class already resolvable" warning
  instead — everything else in an affected file still gets rewritten
  normally. This prompt is never asked under `--apply`; a still-conflicting
  rename is left untouched and reported instead, same as declining would
  do interactively.
- **`HasUuid` has no direct replacement.** Laravel's native
  `Illuminate\Database\Eloquent\Concerns\HasUuids` is a safe mechanical swap
  namespace-wise, but not a pure rename behaviorally — `HasUuids` generates
  ordered, time-sortable UUIDs by default, where `HasUuid` generated random
  ones (see Boot Method above). So it's never applied silently; the command
  asks once, the same way it does for a colliding alias:

  ```
  HasUuids (HasUuid's replacement) is not a pure rename -- see README/CHANGELOG for the behavior difference. Replace it anyway in 6 files?
  ```

  Answer `yes` to replace it everywhere `HasUuid` is found this step, or
  `no` to leave those files untouched and listed under a "has no direct
  replacement" warning instead, same as before. Never asked under
  `--apply`; it's left flagged and reported instead of assuming an answer.

Review the diff and run your own test suite before committing — this is a
straightforward textual rewrite, not a full PHP-aware refactor, so give it
a look rather than trusting it blindly on generated or unusually formatted
code.

**If your database columns don't match the new defaults.** `BootAuthor`,
`BootClient`, `BootMachineName`, `BootMachineNameAsId`, and `Slug` used
to hardcode which attribute they read from/wrote to (`author_id`,
`client_id`, `machine_name`, `name`). Those are now config-driven
(`field`/`reference` under each section — see Configuration below), and the
defaults match the old hardcoded names exactly, so most upgrades need no
config changes at all. If any of your tables already used different column
names for these before upgrading, publish the config and point
`field`/`reference` at your existing columns instead of renaming the
database.

Example below changes the default field name, author_id to user_id.
This was hardcoded to 'author_id' before v0.3.0

```php
// config/coyote6-base.php
'author' => [
    'field' => 'user_id', 
],
```

**`machine_name`'s default generation method changed.** Before v0.3.0,
`BootMachineName`/`BootMachineNameAsId` always lowercased and replaced every
non-alphanumeric character with an underscore — snake_case-shaped output.
The new default is `strictKebab` (dash-separated), since that's now the
package-wide default `machine_name.method`. If your existing `machine_name`
values (or anything reading them) depend on the old underscore-separated
format, publish the config and set `machine_name.method` explicitly to
whichever option in the Method table matches what you already have —
otherwise newly-created records will get dash-separated values alongside
your existing underscore-separated ones.

The upgrade command surfaces this itself rather than leaving it to this
paragraph: if this run's scan finds machine-name generation in use at all
(under either the old or new trait name, so this still fires on a second
run after the rename itself already landed), it checks whether
`coyote6-base`'s config is published yet, offers to publish it if not, then
asks which `machine_name.method` this app should use — defaulting to
whatever the config already has, so pressing enter keeps the current value:

```
machine_name.method is currently "strictKebab". v0.3.0 changed the package default to strictKebab (dash-separated); every version before it always generated snake_case-shaped names, closest to strictSnake. Which method should this app use? (@see https://packagist.org/packages/coyote6/laravel-str and https://packagist.org/packages/coyote6/laravel-base)
```

Picking a different method updates the published config's `machine_name.method`
line directly (a targeted text swap, not a full rewrite of the file, so the
config's own per-method documentation comments are left alone). Never asked
under `--apply`; it prints the same reminder instead and leaves the config
untouched.

## Directory Structure

- `src/Traits/Models/` — Traits meant to be used directly on Eloquent models: `BootTraits`, `GetAsOptions`, `GetAsOptionsAbbr`, `GetBySlug`.
- `src/Traits/Models/Boot/` — Creation-helper traits that provide the `create*` hook methods `BootTraits` looks for via `method_exists()`: `Author`, `OriginalAuthor`, `Client`, `MachineName`, `MachineNameAsId`, `Slug`, and the shared internal helper `ResolvesMachineName`. Always used alongside `BootTraits` on the model, never alone.
- `src/Traits/Database/` — Traits meant to be used on service providers, migrations, or other database-related classes: `DropsIndexes`, `ServiceProviderSeedsDb`.
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

For UUID primary keys, use Laravel's native `Illuminate\Database\Eloquent\Concerns\HasUuids` trait — this package no longer ships its own `Uuid` method.

### Creation Helpers (require `BootTraits`)

All under `Coyote6\LaravelBase\Traits\Models\Boot`:

- **`Author`** — sets `author.field` (default `author_id`) to the current user's id, unless already set (so an explicitly bulk-filled value, e.g. from an import, is preserved rather than overwritten).
- **`OriginalAuthor`** — sets `original_author.field` (default `original_author_id`) to the current user's id the same way, independently of `Author`. Meant as a permanent, foreign-key-free record of who created a row, for schemas where `author_id` has an `ON DELETE SET NULL` foreign key back to `users`.
- **`Client`** — sets `client.field` (default `client_id`) to the current user's `client.reference` attribute (default `client_id`), via `getCurrentUserClientId()`.
- **`MachineName`** — sets `machine_name.field` (default `machine_name`) from `machine_name.reference` (default `name`), via `ResolvesMachineName`.
- **`MachineNameAsId`** — same as `MachineName`, but writes to the model's primary key instead of a separate field. Use one or the other, never both — they share the `createMachineName` method name and will fatal on a trait collision if combined.
- **`Slug`** — sets `slug.field` (default `slug`) from `slug.reference` (default `name`) via `Str::slug()`, using `slug.separator`/`slug.language`/`slug.dictionary`.
- **`ResolvesMachineName`** — internal; composed by `MachineName` and `MachineNameAsId`, not meant to be `use`d directly on a model. Provides `resolveMachineName()` and `resolveMachineNameReference()`.

If your app already has its own `Author`/`Client`/etc. class and needs both,
alias the import (`use ...\Boot\Client as BootClient;`) — see "Upgrade From
0.2.7" above for why the automated upgrade command always does this by
default.

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

    use HasFactory;
    use Author;
    use Client;
    use MachineName;
    use Slug;
    use BootTraits;

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
<?php

use Coyote6\LaravelBase\Traits\Models\BootTraits;
use Coyote6\LaravelBase\Traits\Models\Boot\Author;
use Coyote6\LaravelBase\Traits\Models\Boot\OriginalAuthor;

class Example extends Model {
    
    use Author;
    use OriginalAuthor;
    use BootTraits;
    
    // ...
    protected $fillable = [
        'id', 
        'author_id', 
        'original_author_id',
    ];
}
```

### MachineNameAsId

When the machine name itself should be the primary key, use `MachineNameAsId` instead of `MachineName`:

```php
<?php

use Coyote6\LaravelBase\Traits\Models\Boot\MachineNameAsId;

class Example extends Model {
    
    use MachineNameAsId;
    use BootTraits;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 
        'name',
    ];
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

    use HasFactory;
    use GetAsOptions;

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
