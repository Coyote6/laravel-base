<?php

namespace Coyote6\LaravelBase\Tests\Fixtures;

use Coyote6\LaravelBase\Traits\Files\ReadsCsv;

class TestReadsCsv
{
    use ReadsCsv;

    public function readCsv(string $pathToFile): array
    {
        return $this->getItemsFromCSV($pathToFile);
    }
}
