<?php

declare(strict_types=1);

namespace Oroshi\Core\Common;

use Daikon\DataStructure\TypedMapTrait;
use Daikon\EventSourcing\EventStore\UnitOfWorkInterface;

final class UnitOfWorkMap implements \IteratorAggregate, \Countable
{
    use TypedMapTrait;

    public function __construct(array $unitsOfWork = [])
    {
        $this->init($unitsOfWork, UnitOfWorkInterface::class);
    }
}
