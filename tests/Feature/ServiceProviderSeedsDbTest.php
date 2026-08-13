<?php

use Coyote6\LaravelBase\Tests\Fixtures\TestSeedsDbProvider;
use Illuminate\Support\Facades\DB;

// seedDbOnCommand()'s own event-registration wiring (Event::listen on
// CommandFinished, gated by runningInConsole()) isn't covered here -- it's
// almost entirely Laravel's console/event machinery rather than this
// package's logic, and simulating a real CommandFinished dispatch accurately
// is disproportionately fragile for what it would prove. isSeedCommand()'s
// argv parsing, getClassesFromFile()'s class extraction, and addSeeds()'s
// glob-and-seed loop -- the actual logic this trait owns -- are covered
// directly below.

it('detects a db:seed command from argv, and rejects --class/help/-h variants', function () {
    $provider = new TestSeedsDbProvider(app());

    // isSeedCommand() reads argv via the Request facade, which serves from the
    // bound request's already-captured server bag, not the live $_SERVER
    // superglobal -- so the bag itself has to be updated for the trait to see it.
    $setArgv = fn (array $argv) => app('request')->server->set('argv', $argv);

    $setArgv(['artisan', 'db:seed']);
    expect($provider->isSeedCommandPublic())->toBeTrue();

    $setArgv(['artisan', '--seed']);
    expect($provider->isSeedCommandPublic())->toBeTrue();

    $setArgv(['artisan', 'db:seed', '--class=SomeSeeder']);
    expect($provider->isSeedCommandPublic())->toBeFalse();

    $setArgv(['artisan', 'db:seed', 'help']);
    expect($provider->isSeedCommandPublic())->toBeFalse();

    $setArgv(['artisan', 'migrate']);
    expect($provider->isSeedCommandPublic())->toBeFalse();
});

it('gets fully-qualified class names declared in a file', function () {
    $provider = new TestSeedsDbProvider(app());

    $path = tempnam(sys_get_temp_dir(), 'seeder').'.php';
    file_put_contents($path, <<<'PHP'
    <?php

    namespace Coyote6\LaravelBase\Tests\TempFixtures;

    class ExampleSeederForClassParsing
    {
    }
    PHP);

    $classes = $provider->getClassesFromFilePublic($path);

    unlink($path);

    expect($classes)->toBe(['Coyote6\LaravelBase\Tests\TempFixtures\ExampleSeederForClassParsing']);
});

it('runs every seeder class found in the seeder directory', function () {
    $dir = sys_get_temp_dir().'/coyote6-base-seeders-'.uniqid();
    mkdir($dir);

    $seederPath = $dir.'/MarkerSeeder.php';
    file_put_contents($seederPath, <<<'PHP'
    <?php

    namespace Coyote6\LaravelBase\Tests\TempFixtures;

    use Illuminate\Database\Seeder;
    use Illuminate\Support\Facades\DB;

    class MarkerSeeder extends Seeder
    {
        public function run(): void
        {
            DB::table('test_option_models')->insert(['name' => 'SeededMarker']);
        }
    }
    PHP);

    require_once $seederPath;

    $provider = new TestSeedsDbProvider(app());
    $provider->setSeederDirPublic($dir);
    $provider->addSeedsPublic();

    unlink($seederPath);
    rmdir($dir);

    expect(DB::table('test_option_models')->where('name', 'SeededMarker')->exists())->toBeTrue();
});
