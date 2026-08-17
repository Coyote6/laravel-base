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
        ->toContain('use Coyote6\LaravelBase\Traits\Models\Boot\Author as BootAuthor;')
        ->toContain('use BootAuthor;')
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
        ->toContain('use BootAuthor;')
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
        ->toContain('use Coyote6\LaravelBase\Traits\Models\Boot\Author as BootAuthor;')
        ->toContain('use Coyote6\LaravelBase\Traits\Models\Boot\Client as BootClient;')
        ->toContain('use Coyote6\LaravelBase\Traits\Models\Boot\MachineName as BootMachineName;')
        ->toContain('use Coyote6\LaravelBase\Traits\Models\Boot\MachineNameAsId as BootMachineNameAsId;')
        ->toContain('use Coyote6\LaravelBase\Traits\Models\BootTraits;')
        ->toContain('use BootAuthor, BootClient, BootMachineName, BootMachineNameAsId, BootTraits;')
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

it('declines the interactive HasUuid replacement, leaving the file untouched', function () {
    $dir = 'upgrade-command-uuid-decline';
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

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir])
        ->expectsConfirmation(
            "HasUuids (HasUuid's replacement) is not a pure rename -- see README/CHANGELOG for the behavior difference. Replace it anyway in 1 file?",
            'no'
        )
        ->expectsOutputToContain('HasUuid has no direct replacement')
        ->assertSuccessful();

    $content = File::get($path);
    File::deleteDirectory(base_path($dir));

    expect($content)
        ->toContain('use Coyote6\LaravelBase\Traits\HasUuid;')
        ->toContain('use HasUuid;');
});

it('applies the HasUuid replacement when the developer confirms', function () {
    $dir = 'upgrade-command-uuid-confirm';
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

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir])
        ->expectsConfirmation(
            "HasUuids (HasUuid's replacement) is not a pure rename -- see README/CHANGELOG for the behavior difference. Replace it anyway in 1 file?",
            'yes'
        )
        ->expectsConfirmation('Found 1 file that would change. Apply the changes?', 'yes')
        ->assertSuccessful();

    $content = File::get($path);
    File::deleteDirectory(base_path($dir));

    expect($content)
        ->toContain('use Illuminate\Database\Eloquent\Concerns\HasUuids;')
        ->toContain('use HasUuids;')
        ->not->toContain('HasUuid;')
        ->not->toContain('use HasUuid,');
});

it('never prompts for the HasUuid replacement under --apply, leaving it flagged instead', function () {
    $dir = 'upgrade-command-uuid-apply-no-prompt';
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

    // No expectsConfirmation() stub for the replacement question -- if the
    // command tried to ask under --apply, Mockery would throw for an
    // unexpected confirm() call.
    $this->artisan('coyote6-base:upgrade', ['--path' => $dir, '--apply' => true])
        ->expectsOutputToContain('HasUuid has no direct replacement')
        ->assertSuccessful();

    $content = File::get($path);
    File::deleteDirectory(base_path($dir));

    expect($content)->toContain('use Coyote6\LaravelBase\Traits\HasUuid;');
});

it('asks once about the HasUuid replacement even when many files share it', function () {
    $dir = 'upgrade-command-uuid-dedup';
    File::ensureDirectoryExists(base_path($dir));

    foreach (['One', 'Two'] as $name) {
        File::put(base_path("{$dir}/{$name}.php"), <<<PHP
        <?php

        use Coyote6\LaravelBase\Traits\HasUuid;

        class {$name}
        {
            use HasUuid;
        }
        PHP);
    }

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir])
        ->expectsConfirmation(
            "HasUuids (HasUuid's replacement) is not a pure rename -- see README/CHANGELOG for the behavior difference. Replace it anyway in 2 files?",
            'yes'
        )
        ->expectsConfirmation('Found 2 files that would change. Apply the changes?', 'yes')
        ->assertSuccessful();

    foreach (['One', 'Two'] as $name) {
        $content = File::get(base_path("{$dir}/{$name}.php"));

        expect($content)
            ->toContain('use Illuminate\Database\Eloquent\Concerns\HasUuids;')
            ->toContain('use HasUuids;');
    }

    File::deleteDirectory(base_path($dir));
});

it('prompts to publish the config when machine-name traits are found, and declining leaves the note without writing anything', function () {
    $dir = 'upgrade-command-machine-name-decline-publish';
    File::ensureDirectoryExists(base_path($dir));

    $path = base_path("{$dir}/Example.php");
    File::put($path, <<<'PHP'
    <?php

    use Coyote6\LaravelBase\Traits\HasMachineName;

    class Example
    {
        use HasMachineName;
    }
    PHP);

    $configPath = config_path('coyote6-base.php');
    File::delete($configPath);

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir])
        ->expectsConfirmation(
            "This app uses machine-name generation (MachineName/MachineNameAsId), but hasn't published coyote6-base's config yet. Publish it now so machine_name.method can be reviewed?",
            'no'
        )
        ->expectsOutputToContain('machine_name.method defaults to strictKebab as of v0.3.0')
        ->expectsConfirmation('Found 1 file that would change. Apply the changes?', 'no')
        ->assertSuccessful();

    File::deleteDirectory(base_path($dir));

    expect(File::exists($configPath))->toBeFalse();
});

it('publishes the config and applies a chosen machine_name.method when the developer confirms both', function () {
    $dir = 'upgrade-command-machine-name-publish-and-change';
    File::ensureDirectoryExists(base_path($dir));

    $path = base_path("{$dir}/Example.php");
    File::put($path, <<<'PHP'
    <?php

    use Coyote6\LaravelBase\Traits\HasMachineName;

    class Example
    {
        use HasMachineName;
    }
    PHP);

    $configPath = config_path('coyote6-base.php');
    File::delete($configPath);

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir])
        ->expectsConfirmation(
            "This app uses machine-name generation (MachineName/MachineNameAsId), but hasn't published coyote6-base's config yet. Publish it now so machine_name.method can be reviewed?",
            'yes'
        )
        ->expectsQuestion(
            'machine_name.method is currently "strictKebab". v0.3.0 changed the package default to strictKebab (dash-separated); every version before it always generated snake_case-shaped names, closest to strictSnake. Which method should this app use? (@see https://packagist.org/packages/coyote6/laravel-str and https://packagist.org/packages/coyote6/laravel-base)',
            'strictSnake'
        )
        ->expectsConfirmation('Found 1 file that would change. Apply the changes?', 'no')
        ->assertSuccessful();

    $configContents = File::get($configPath);
    File::delete($configPath);
    File::deleteDirectory(base_path($dir));

    expect($configContents)->toContain("'method' => 'strictSnake',");
});

it('does not touch the config file when the developer picks the same method it already has', function () {
    $dir = 'upgrade-command-machine-name-no-change';
    File::ensureDirectoryExists(base_path($dir));

    $path = base_path("{$dir}/Example.php");
    File::put($path, <<<'PHP'
    <?php

    use Coyote6\LaravelBase\Traits\HasMachineName;

    class Example
    {
        use HasMachineName;
    }
    PHP);

    $configPath = config_path('coyote6-base.php');
    File::delete($configPath);

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir])
        ->expectsConfirmation(
            "This app uses machine-name generation (MachineName/MachineNameAsId), but hasn't published coyote6-base's config yet. Publish it now so machine_name.method can be reviewed?",
            'yes'
        )
        ->expectsQuestion(
            'machine_name.method is currently "strictKebab". v0.3.0 changed the package default to strictKebab (dash-separated); every version before it always generated snake_case-shaped names, closest to strictSnake. Which method should this app use? (@see https://packagist.org/packages/coyote6/laravel-str and https://packagist.org/packages/coyote6/laravel-base)',
            'strictKebab'
        )
        ->expectsConfirmation('Found 1 file that would change. Apply the changes?', 'no')
        ->assertSuccessful();

    $configContents = File::get($configPath);
    File::delete($configPath);
    File::deleteDirectory(base_path($dir));

    expect($configContents)->toContain("'method' => 'strictKebab',");
});

it('skips the publish question when the config is already published', function () {
    $dir = 'upgrade-command-machine-name-already-published';
    File::ensureDirectoryExists(base_path($dir));

    $path = base_path("{$dir}/Example.php");
    File::put($path, <<<'PHP'
    <?php

    use Coyote6\LaravelBase\Traits\HasMachineName;

    class Example
    {
        use HasMachineName;
    }
    PHP);

    $configPath = config_path('coyote6-base.php');
    File::copy(__DIR__.'/../../config/coyote6-base.php', $configPath);

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir])
        ->doesntExpectOutputToContain("hasn't published coyote6-base's config yet")
        ->expectsQuestion(
            'machine_name.method is currently "strictKebab". v0.3.0 changed the package default to strictKebab (dash-separated); every version before it always generated snake_case-shaped names, closest to strictSnake. Which method should this app use? (@see https://packagist.org/packages/coyote6/laravel-str and https://packagist.org/packages/coyote6/laravel-base)',
            'strictKebab'
        )
        ->expectsConfirmation('Found 1 file that would change. Apply the changes?', 'no')
        ->assertSuccessful();

    File::delete($configPath);
    File::deleteDirectory(base_path($dir));
});

it('never checks machine_name.method when no file uses machine-name generation at all', function () {
    $dir = 'upgrade-command-machine-name-not-used';
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

    $configPath = config_path('coyote6-base.php');
    File::delete($configPath);

    // No expectsQuestion()/expectsConfirmation() stub for a publish or
    // method question -- if the command asked either, Mockery would throw
    // for an unexpected call.
    $this->artisan('coyote6-base:upgrade', ['--path' => $dir])
        ->expectsConfirmation('Found 1 file that would change. Apply the changes?', 'no')
        ->doesntExpectOutputToContain('machine_name.method')
        ->assertSuccessful();

    File::deleteDirectory(base_path($dir));

    expect(File::exists($configPath))->toBeFalse();
});

it('still checks machine_name.method on a second run, after the trait rename itself already landed', function () {
    $dir = 'upgrade-command-machine-name-second-run';
    File::ensureDirectoryExists(base_path($dir));

    $path = base_path("{$dir}/Example.php");
    File::put($path, <<<'PHP'
    <?php

    namespace App\Models;

    use Coyote6\LaravelBase\Traits\Models\Boot\MachineName as BootMachineName;
    use Coyote6\LaravelBase\Traits\Models\BootTraits;
    use Illuminate\Database\Eloquent\Model;

    class Example extends Model
    {
        use BootMachineName, BootTraits;
    }
    PHP);

    $configPath = config_path('coyote6-base.php');
    File::delete($configPath);

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir])
        ->expectsOutputToContain('Running v0.3.0 upgrades')
        ->expectsConfirmation(
            "This app uses machine-name generation (MachineName/MachineNameAsId), but hasn't published coyote6-base's config yet. Publish it now so machine_name.method can be reviewed?",
            'no'
        )
        ->expectsOutputToContain('No file changes found.')
        ->assertSuccessful();

    File::deleteDirectory(base_path($dir));

    expect(File::exists($configPath))->toBeFalse();
});

it('never prompts about machine_name.method under --apply, printing the note instead', function () {
    $dir = 'upgrade-command-machine-name-apply-no-prompt';
    File::ensureDirectoryExists(base_path($dir));

    $path = base_path("{$dir}/Example.php");
    File::put($path, <<<'PHP'
    <?php

    use Coyote6\LaravelBase\Traits\HasMachineName;

    class Example
    {
        use HasMachineName;
    }
    PHP);

    $configPath = config_path('coyote6-base.php');
    File::delete($configPath);

    // No expectsQuestion()/expectsConfirmation() stub for the publish
    // question -- if the command asked under --apply, Mockery would throw
    // for an unexpected confirm() call.
    $this->artisan('coyote6-base:upgrade', ['--path' => $dir, '--apply' => true])
        ->expectsOutputToContain('machine_name.method defaults to strictKebab as of v0.3.0')
        ->expectsOutputToContain('not prompted under --apply')
        ->assertSuccessful();

    File::deleteDirectory(base_path($dir));

    expect(File::exists($configPath))->toBeFalse();
});

it('skips a missing directory with a warning instead of failing', function () {
    $this->artisan('coyote6-base:upgrade', ['--path' => 'no-such-directory-here'])
        ->expectsOutputToContain('Skipping missing directory')
        ->assertSuccessful();
});

it('does not flag a conflict when a real Author model exists, since the mandatory BootAuthor alias avoids it', function () {
    $dir = 'upgrade-command-no-conflict-with-mandatory-alias';
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
        ->doesntExpectOutputToContain('collides with an existing class')
        ->assertSuccessful();

    $content = File::get($path);
    File::deleteDirectory(base_path($dir));

    expect($content)
        ->toContain('use Coyote6\LaravelBase\Traits\Models\Boot\Author as BootAuthor;')
        ->toContain('use App\Models\Author;')
        ->toContain('use BootAuthor;')
        ->not->toContain('HasAuthor');
});

it('prompts for a custom alias when even the mandatory BootAuthor alias collides, and leaves the file untouched when declined', function () {
    $dir = 'upgrade-command-conflict';
    File::ensureDirectoryExists(base_path($dir));

    $path = base_path("{$dir}/Book.php");
    File::put($path, <<<'PHP'
    <?php

    namespace App\Models;

    use App\Models\BootAuthor;
    use Coyote6\LaravelBase\Traits\HasAuthor;
    use Illuminate\Database\Eloquent\Model;

    class Book extends Model
    {
        use HasAuthor;

        public function author()
        {
            return $this->belongsTo(BootAuthor::class);
        }
    }
    PHP);

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir])
        ->expectsQuestion(
            "BootAuthor (HasAuthor's replacement) collides with an existing class in 1 file. Please provide a new alias for the trait, or leave blank to skip and manually review",
            ''
        )
        ->expectsOutputToContain('collide with an existing, unrelated class already resolvable')
        ->expectsOutputToContain($path)
        ->assertSuccessful();

    $content = File::get($path);
    File::deleteDirectory(base_path($dir));

    expect($content)
        ->toContain('use Coyote6\LaravelBase\Traits\HasAuthor;')
        ->toContain('use HasAuthor;');
});

it('applies a developer-chosen alias when even the mandatory BootAuthor alias collides', function () {
    $dir = 'upgrade-command-conflict-custom-alias';
    File::ensureDirectoryExists(base_path($dir));

    $path = base_path("{$dir}/Book.php");
    File::put($path, <<<'PHP'
    <?php

    namespace App\Models;

    use App\Models\BootAuthor;
    use Coyote6\LaravelBase\Traits\HasAuthor;
    use Illuminate\Database\Eloquent\Model;

    class Book extends Model
    {
        use HasAuthor;

        public function author()
        {
            return $this->belongsTo(BootAuthor::class);
        }
    }
    PHP);

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir])
        ->expectsQuestion(
            "BootAuthor (HasAuthor's replacement) collides with an existing class in 1 file. Please provide a new alias for the trait, or leave blank to skip and manually review",
            'AuthorStamp'
        )
        ->expectsConfirmation('Found 1 file that would change. Apply the changes?', 'yes')
        ->assertSuccessful();

    $content = File::get($path);
    File::deleteDirectory(base_path($dir));

    expect($content)
        ->toContain('use Coyote6\LaravelBase\Traits\Models\Boot\Author as AuthorStamp;')
        ->toContain('use App\Models\BootAuthor;')
        ->toContain('use AuthorStamp;')
        ->not->toContain('HasAuthor');
});

it('never prompts for an alias under --apply, leaving a double collision unresolved instead', function () {
    $dir = 'upgrade-command-conflict-apply-no-prompt';
    File::ensureDirectoryExists(base_path($dir));

    $path = base_path("{$dir}/Book.php");
    File::put($path, <<<'PHP'
    <?php

    namespace App\Models;

    use App\Models\BootAuthor;
    use Coyote6\LaravelBase\Traits\HasAuthor;
    use Illuminate\Database\Eloquent\Model;

    class Book extends Model
    {
        use HasAuthor;

        public function author()
        {
            return $this->belongsTo(BootAuthor::class);
        }
    }
    PHP);

    // No expectsQuestion() stub at all -- if the command tried to prompt under
    // --apply, Mockery would throw for an unexpected askQuestion() call.
    $this->artisan('coyote6-base:upgrade', ['--path' => $dir, '--apply' => true])
        ->expectsOutputToContain('collide with an existing, unrelated class already resolvable')
        ->assertSuccessful();

    $content = File::get($path);
    File::deleteDirectory(base_path($dir));

    expect($content)->toContain('use Coyote6\LaravelBase\Traits\HasAuthor;');
});

it('does not flag a conflict when the developer already aliased the old import', function () {
    $dir = 'upgrade-command-conflict-aliased';
    File::ensureDirectoryExists(base_path($dir));

    $path = base_path("{$dir}/Book.php");
    File::put($path, <<<'PHP'
    <?php

    namespace App\Models;

    use App\Models\BootAuthor;
    use Coyote6\LaravelBase\Traits\HasAuthor as AuthorTrait;
    use Illuminate\Database\Eloquent\Model;

    class Book extends Model
    {
        use AuthorTrait;

        public function author()
        {
            return $this->belongsTo(BootAuthor::class);
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
        ->toContain('use App\Models\BootAuthor;')
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

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir])
        ->expectsQuestion(
            "BootAuthor (HasAuthor's replacement) collides with an existing class in 1 file. Please provide a new alias for the trait, or leave blank to skip and manually review",
            ''
        )
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

    use App\Models\BootAuthor;
    use Coyote6\LaravelBase\Traits\HasAuthor;
    use Coyote6\LaravelBase\Traits\HasClient;
    use Illuminate\Database\Eloquent\Model;

    class Book extends Model
    {
        use HasAuthor, HasClient;

        public function author()
        {
            return $this->belongsTo(BootAuthor::class);
        }
    }
    PHP);

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir])
        ->expectsQuestion(
            "BootAuthor (HasAuthor's replacement) collides with an existing class in 1 file. Please provide a new alias for the trait, or leave blank to skip and manually review",
            ''
        )
        ->expectsConfirmation('Found 1 file that would change. Apply the changes?', 'yes')
        ->assertSuccessful();

    $content = File::get($path);
    File::deleteDirectory(base_path($dir));

    expect($content)
        ->toContain('use Coyote6\LaravelBase\Traits\HasAuthor;')
        ->toContain('use Coyote6\LaravelBase\Traits\Models\Boot\Client as BootClient;')
        ->not->toContain('HasClient');
});

it('asks once per distinct trait collision even when many files share it', function () {
    $dir = 'upgrade-command-conflict-dedup';
    File::ensureDirectoryExists(base_path($dir));

    foreach (['One', 'Two', 'Three'] as $name) {
        File::put(base_path("{$dir}/{$name}.php"), <<<PHP
        <?php

        namespace App\Models;

        use App\Models\BootAuthor;
        use Coyote6\LaravelBase\Traits\HasAuthor;
        use Illuminate\Database\Eloquent\Model;

        class {$name} extends Model
        {
            use HasAuthor;

            public function author()
            {
                return \$this->belongsTo(BootAuthor::class);
            }
        }
        PHP);
    }

    $this->artisan('coyote6-base:upgrade', ['--path' => $dir])
        ->expectsQuestion(
            "BootAuthor (HasAuthor's replacement) collides with an existing class in 3 files. Please provide a new alias for the trait, or leave blank to skip and manually review",
            'AuthorStamp'
        )
        ->expectsConfirmation('Found 3 files that would change. Apply the changes?', 'yes')
        ->assertSuccessful();

    foreach (['One', 'Two', 'Three'] as $name) {
        $content = File::get(base_path("{$dir}/{$name}.php"));

        expect($content)
            ->toContain('use Coyote6\LaravelBase\Traits\Models\Boot\Author as AuthorStamp;')
            ->toContain('use AuthorStamp;');
    }

    File::deleteDirectory(base_path($dir));
});
