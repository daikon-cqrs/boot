<?php

declare(strict_types=1);

namespace Oroshi\Core\Fixture;

use Daikon\DataStructure\TypedListTrait;
use Daikon\Interop\ToNativeInterface;

final class FixtureList implements \IteratorAggregate, \Countable, ToNativeInterface
{
    use TypedListTrait;

    public function __construct(iterable $fixtures = [])
    {
        $this->init($fixtures, FixtureInterface::class);
    }

    public function toNative(): array
    {
        $fixtures = [];
        foreach ($this as $fixture) {
            $fixtures[] = $fixture->toNative();
        }
        return $fixtures;
    }
}
