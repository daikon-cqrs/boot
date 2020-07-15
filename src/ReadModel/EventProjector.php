<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\ReadModel;

use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;
use Daikon\ReadModel\Projector\EventProjectorInterface;
use Daikon\ReadModel\Projector\ProjectorInterface;

final class EventProjector implements EventProjectorInterface
{
    private array $eventExpressions;

    private ProjectorInterface $projector;

    public function __construct(array $eventExpressions, ProjectorInterface $projector)
    {
        $this->eventExpressions = $eventExpressions;
        $this->projector = $projector;
    }

    public function matches(DomainEventInterface $event): bool
    {
        $eventFqcn = get_class($event);
        return array_reduce(
            $this->eventExpressions,
            fn(bool $carry, string $eventExpression): bool => $carry || $eventExpression === $eventFqcn,
            false
        );
    }

    public function getProjector(): ProjectorInterface
    {
        return $this->projector;
    }

    public function getEventExpressions(): array
    {
        return $this->eventExpressions;
    }
}
