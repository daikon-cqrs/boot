<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Fixture;

use Countable;
use Daikon\DataStructure\TypedMapTrait;
use IteratorAggregate;

final class FixtureTargetMap implements IteratorAggregate, Countable
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
