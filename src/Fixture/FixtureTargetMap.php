<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Fixture;

use Daikon\DataStructure\TypedMapInterface;
use Daikon\DataStructure\TypedMapTrait;

final class FixtureTargetMap implements TypedMapInterface
{
    use TypedMapTrait;

    public function __construct(iterable $fixtureTargets = [])
    {
        $this->init($fixtureTargets, [FixtureTargetInterface::class]);
    }

    public function getEnabledTargets(): self
    {
        return $this->filter(
            fn(string $name, FixtureTargetInterface $target): bool => $target->isEnabled()
        );
    }
}
