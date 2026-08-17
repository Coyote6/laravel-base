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
