<?php

declare(strict_types=1);

namespace Oroshi\Core\Fixture;

use Daikon\DataStructure\TypedMapTrait;

final class FixtureLoaderMap implements \IteratorAggregate, \Countable
{
    use TypedMapTrait;

    public function __construct(iterable $fixtureLoaders = [])
    {
        $this->init($fixtureLoaders, FixtureLoaderInterface::class);
    }
}
