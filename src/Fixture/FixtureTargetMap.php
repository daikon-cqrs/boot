<?php

declare(strict_types=1);

namespace Oroshi\Core\Fixture;

use Daikon\DataStructure\TypedMapTrait;

final class FixtureTargetMap implements \IteratorAggregate, \Countable
{
    use TypedMapTrait;

    public function __construct(iterable $fixtureTargets = [])
    {
        $this->init($fixtureTargets, FixtureTargetInterface::class);
    }

    public function getEnabledTargets(): self
    {
        return new self(
            $this->compositeMap->filter(
                function (string $fixtureName, FixtureTargetInterface $fixtureTarget): bool {
                    return $fixtureTarget->isEnabled();
                }
            )
        );
    }
}
