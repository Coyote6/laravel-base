<?php

use Illuminate\Support\Facades\File;

it('is a dry-run by default -- declining the confirmation leaves the file untouched', function () {
    $dir = 'upgrade-command-dry-run';
    File::ensureDirectoryExists(base_path($dir));

    $path = base_path("{$dir}/Example.php");
    File::put($path, <<<'PHP'
    <?php

    use Coyote6\LaravelBase\Traits\HasAuthor;

    class Example
    {
        use HasAuthor;
    }
    PHP);

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir])
        ->expectsOutputToContain('Running v0.3.0 upgrades')
        ->expectsConfirmation('Found 1 file that would change. Apply the changes?', 'no')
        ->assertSuccessful();

    expect(File::get($path))->toContain('HasAuthor');

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir, '--apply' => true])->assertSuccessful();

    $updated = File::get($path);
    File::deleteDirectory(base_path($dir));

    expect($updated)
        ->toContain('use Coyote6\LaravelBase\Traits\Models\Boot\Author;')
        ->toContain('use Author;')
        ->not->toContain('HasAuthor');
});

it('applies changes when the developer confirms, without needing --apply', function () {
    $dir = 'upgrade-command-confirm-yes';
    File::ensureDirectoryExists(base_path($dir));

    $path = base_path("{$dir}/Example.php");
    File::put($path, <<<'PHP'
    <?php

    use Coyote6\LaravelBase\Traits\HasAuthor;

    class Example
    {
        use HasAuthor;
    }
    PHP);

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir])
        ->expectsConfirmation('Found 1 file that would change. Apply the changes?', 'yes')
        ->assertSuccessful();

    $updated = File::get($path);
    File::deleteDirectory(base_path($dir));

    expect($updated)
        ->toContain('use Author;')
        ->not->toContain('HasAuthor');
});

it('pluralizes the file count correctly', function () {
    $dir = 'upgrade-command-plural';
    File::ensureDirectoryExists(base_path($dir));

    File::put(base_path("{$dir}/One.php"), <<<'PHP'
    <?php

    use Coyote6\LaravelBase\Traits\HasAuthor;

    class One
    {
        use HasAuthor;
    }
    PHP);

    File::put(base_path("{$dir}/Two.php"), <<<'PHP'
    <?php

    use Coyote6\LaravelBase\Traits\HasClient;

    class Two
    {
        use HasClient;
    }
    PHP);

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir])
        ->expectsConfirmation('Found 2 files that would change. Apply the changes?', 'no')
        ->assertSuccessful();

    File::deleteDirectory(base_path($dir));
});

it('reports no file changes found when a step has nothing to do', function () {
    $dir = 'upgrade-command-no-changes';
    File::ensureDirectoryExists(base_path($dir));

    File::put(base_path("{$dir}/Example.php"), <<<'PHP'
    <?php

    class Example
    {
    }
    PHP);

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir])
        ->expectsOutputToContain('Running v0.3.0 upgrades')
        ->expectsOutputToContain('No file changes found.')
        ->assertSuccessful();

    File::deleteDirectory(base_path($dir));
});

it('rewrites both the FQCN import and the bare use-statement for every renamed trait, including the HasMachineName/HasMachineNameAsId prefix collision', function () {
    $dir = 'upgrade-command-full';
    File::ensureDirectoryExists(base_path($dir));

    $path = base_path("{$dir}/Example.php");
    File::put($path, <<<'PHP'
    <?php

    namespace App\Models;

    use Coyote6\LaravelBase\Traits\HasAuthor;
    use Coyote6\LaravelBase\Traits\HasClient;
    use Coyote6\LaravelBase\Traits\HasMachineName;
    use Coyote6\LaravelBase\Traits\HasMachineNameAsId;
    use Coyote6\LaravelBase\Traits\BootTraits;
    use Illuminate\Database\Eloquent\Model;

    class Example extends Model
    {
        use HasAuthor, HasClient, HasMachineName, HasMachineNameAsId, BootTraits;
    }
    PHP);

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir, '--apply' => true])->assertSuccessful();

    $updated = File::get($path);
    File::deleteDirectory(base_path($dir));

    expect($updated)
        ->toContain('use Coyote6\LaravelBase\Traits\Models\Boot\Author;')
        ->toContain('use Coyote6\LaravelBase\Traits\Models\Boot\Client;')
        ->toContain('use Coyote6\LaravelBase\Traits\Models\Boot\MachineName;')
        ->toContain('use Coyote6\LaravelBase\Traits\Models\Boot\MachineNameAsId;')
        ->toContain('use Coyote6\LaravelBase\Traits\Models\BootTraits;')
        ->toContain('use Author, Client, MachineName, MachineNameAsId, BootTraits;')
        ->not->toContain('HasAuthor')
        ->not->toContain('HasClient')
        // Only check the exact old identifiers, not "MachineName"/"MachineNameAsId"
        // themselves, since those substrings correctly remain as part of the
        // new names.
        ->not->toContain('HasMachineName;')
        ->not->toContain('HasMachineNameAsId;')
        ->not->toContain('use HasMachineName,')
        ->not->toContain('use HasMachineNameAsId,');
});

it('flags HasUuid without rewriting it, since there is no direct replacement', function () {
    $dir = 'upgrade-command-uuid';
    File::ensureDirectoryExists(base_path($dir));

    $path = base_path("{$dir}/Example.php");
    File::put($path, <<<'PHP'
    <?php

    use Coyote6\LaravelBase\Traits\HasUuid;

    class Example
    {
        use HasUuid;
    }
    PHP);

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir, '--apply' => true])
        ->expectsOutputToContain('HasUuid has no direct replacement')
        ->assertSuccessful();

    $content = File::get($path);
    File::deleteDirectory(base_path($dir));

    expect($content)->toContain('HasUuid');
});

it('skips a missing directory with a warning instead of failing', function () {
    $this->artisan('coyote6-base:upgrade', ['--path' => 'no-such-directory-here'])
        ->expectsOutputToContain('Skipping missing directory')
        ->assertSuccessful();
});

it('flags a trait rename that would collide with an existing, unrelated import, and leaves it untouched', function () {
    $dir = 'upgrade-command-conflict';
    File::ensureDirectoryExists(base_path($dir));

    $path = base_path("{$dir}/Book.php");
    File::put($path, <<<'PHP'
    <?php

    namespace App\Models;

    use App\Models\Author;
    use Coyote6\LaravelBase\Traits\HasAuthor;
    use Illuminate\Database\Eloquent\Model;

    class Book extends Model
    {
        use HasAuthor;

        public function author()
        {
            return $this->belongsTo(Author::class);
        }
    }
    PHP);

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir, '--apply' => true])
        ->expectsOutputToContain('collide with an existing, unrelated class already resolvable')
        ->expectsOutputToContain($path)
        ->assertSuccessful();

    $content = File::get($path);
    File::deleteDirectory(base_path($dir));

    expect($content)
        ->toContain('use Coyote6\LaravelBase\Traits\HasAuthor;')
        ->toContain('use HasAuthor;');
});

it('does not flag a conflict when the developer already aliased the old import', function () {
    $dir = 'upgrade-command-conflict-aliased';
    File::ensureDirectoryExists(base_path($dir));

    $path = base_path("{$dir}/Book.php");
    File::put($path, <<<'PHP'
    <?php

    namespace App\Models;

    use App\Models\Author;
    use Coyote6\LaravelBase\Traits\HasAuthor as AuthorTrait;
    use Illuminate\Database\Eloquent\Model;

    class Book extends Model
    {
        use AuthorTrait;

        public function author()
        {
            return $this->belongsTo(Author::class);
        }
    }
    PHP);

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir, '--apply' => true])
        ->doesntExpectOutputToContain('collide with an existing, unrelated class already resolvable')
        ->assertSuccessful();

    $content = File::get($path);
    File::deleteDirectory(base_path($dir));

    expect($content)
        ->toContain('use Coyote6\LaravelBase\Traits\Models\Boot\Author as AuthorTrait;')
        ->toContain('use App\Models\Author;')
        ->toContain('use AuthorTrait;');
});

it('flags a trait rename that would collide with a same-namespace class that has no explicit import', function () {
    $dir = 'upgrade-command-conflict-same-namespace';
    File::ensureDirectoryExists(base_path($dir));

    $path = base_path("{$dir}/Book.php");
    File::put($path, <<<'PHP'
    <?php

    namespace Coyote6\LaravelBase\Tests\Fixtures;

    use Coyote6\LaravelBase\Traits\HasAuthor;
    use Illuminate\Database\Eloquent\Model;

    class Book extends Model
    {
        use HasAuthor;
    }
    PHP);

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir, '--apply' => true])
        ->expectsOutputToContain('collide with an existing, unrelated class already resolvable')
        ->expectsOutputToContain($path)
        ->assertSuccessful();

    $content = File::get($path);
    File::deleteDirectory(base_path($dir));

    expect($content)
        ->toContain('use Coyote6\LaravelBase\Traits\HasAuthor;')
        ->toContain('use HasAuthor;');
});

it('still rewrites a non-conflicting trait in the same file as a conflicting one', function () {
    $dir = 'upgrade-command-conflict-partial';
    File::ensureDirectoryExists(base_path($dir));

    $path = base_path("{$dir}/Book.php");
    File::put($path, <<<'PHP'
    <?php

    namespace App\Models;

    use App\Models\Author;
    use Coyote6\LaravelBase\Traits\HasAuthor;
    use Coyote6\LaravelBase\Traits\HasClient;
    use Illuminate\Database\Eloquent\Model;

    class Book extends Model
    {
        use HasAuthor, HasClient;

        public function author()
        {
            return $this->belongsTo(Author::class);
        }
    }
    PHP);

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir, '--apply' => true])->assertSuccessful();

    $content = File::get($path);
    File::deleteDirectory(base_path($dir));

    expect($content)
        ->toContain('use Coyote6\LaravelBase\Traits\HasAuthor;')
        ->toContain('use Coyote6\LaravelBase\Traits\Models\Boot\Client;')
        ->not->toContain('HasClient');
});
