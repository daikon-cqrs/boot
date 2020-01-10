<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Service;

use Daikon\DataStructure\TypedMapInterface;
use Daikon\DataStructure\TypedMapTrait;

final class ServiceDefinitionMap implements TypedMapInterface
{
    use TypedMapTrait;

    public function __construct(iterable $serviceDefinitions = [])
    {
        $this->init($serviceDefinitions, [ServiceDefinitionInterface::class]);
    }
}
