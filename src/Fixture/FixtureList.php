<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Fixture;

use Countable;
use Daikon\DataStructure\TypedListTrait;
use Daikon\Interop\ToNativeInterface;
use IteratorAggregate;

final class FixtureList implements IteratorAggregate, Countable, ToNativeInterface
{
    use TypedListTrait;

    public function __construct(iterable $fixtures = [])
    {
        $this->init($fixtures, FixtureInterface::class);
    }

    public function sortByVersion(): self
    {
        $copy = clone $this;
        $copy->compositeVector->sort(function (FixtureInterface $a, FixtureInterface $b): bool {
            return $a->getVersion() > $b->getVersion();
        });
        return $copy;
    }
}
