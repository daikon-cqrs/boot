<?php

declare(strict_types=1);

namespace Oroshi\Core\Fixture;

interface FixtureLoaderInterface
{
    public function load(): FixtureList;
}
