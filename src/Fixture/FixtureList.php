<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Fixture;

use Daikon\DataStructure\TypedListInterface;
use Daikon\DataStructure\TypedListTrait;

final class FixtureList implements TypedListInterface
{
    use TypedListTrait;

    public function __construct(iterable $fixtures = [])
    {
        $this->init($fixtures, [FixtureInterface::class]);
    }

    public function sortByVersion(): self
    {
        return $this->sort(
            fn(FixtureInterface $a, FixtureInterface $b): bool => $a->getVersion() > $b->getVersion()
        );
    }
}
