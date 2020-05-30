<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Crate;

use Daikon\DataStructure\TypedMapInterface;
use Daikon\DataStructure\TypedMapTrait;

final class CrateMap implements TypedMapInterface
{
    use TypedMapTrait;

    public function __construct(iterable $crates = [])
    {
        $this->init($crates, [CrateInterface::class]);
    }

    public function getLocations(): array
    {
        $locations = [];
        foreach ($this as $crate) {
            $locations[] = $crate->getLocation();
        }
        return $locations;
    }
}
