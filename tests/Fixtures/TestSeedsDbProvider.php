<?php

namespace Coyote6\LaravelBase\Tests\Fixtures;

use Coyote6\LaravelBase\Traits\Database\ServiceProviderSeedsDb;
use Illuminate\Support\ServiceProvider;

class TestSeedsDbProvider extends ServiceProvider
{
    use ServiceProviderSeedsDb;

    public function seedDbOnCommandPublic($dir): void
    {
        $this->seedDbOnCommand($dir);
    }

    public function isSeedCommandPublic(): bool
    {
        return $this->isSeedCommand();
    }

    public function getClassesFromFilePublic(string $filename): array
    {
        return $this->getClassesFromFile($filename);
    }

    public function setSeederDirPublic(string $dir): void
    {
        $this->seederDir = $dir;
    }

    public function addSeedsPublic(): void
    {
        $this->addSeeds();
    }
}
