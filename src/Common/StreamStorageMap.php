<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Common;

use Countable;
use Daikon\DataStructure\TypedMapTrait;
use Daikon\EventSourcing\EventStore\Storage\StreamStorageInterface;
use IteratorAggregate;

final class StreamStorageMap implements IteratorAggregate, Countable
{
    use TypedMapTrait;

    public function __construct(iterable $streamStores = [])
    {
        $this->init($streamStores, StreamStorageInterface::class);
    }
}
