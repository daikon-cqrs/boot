<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\ReadModel;

use Daikon\EventSourcing\Aggregate\Event\DomainEventInterface;
use Daikon\EventSourcing\EventStore\Commit\CommitInterface;
use Daikon\Interop\Assertion;
use Daikon\MessageBus\EnvelopeInterface;
use Daikon\ReadModel\Projection\ProjectionInterface;
use Daikon\ReadModel\Projector\ProjectorInterface;
use Daikon\ReadModel\Repository\RepositoryInterface;

final class StandardProjector implements ProjectorInterface
{
    private RepositoryInterface $repository;

    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function handle(EnvelopeInterface $envelope): void
    {
        /** @var CommitInterface $commit */
        $commit = $envelope->getMessage();
        Assertion::implementsInterface($commit, CommitInterface::class);

        if ($commit->getSequence()->isInitial()) {
            $projection = $this->repository->makeProjection();
        } else {
            $aggregateId = (string)$commit->getAggregateId();
            $projection = $this->repository->findById($aggregateId)->getFirst();
        }

        Assertion::implementsInterface($projection, ProjectionInterface::class);

        /**
         * @var ProjectionInterface $projection
         * @var DomainEventInterface $event
         */
        foreach ($commit->getEventLog() as $event) {
            $projection = $projection->applyEvent($event);
        }

        $this->repository->persist($projection);
    }
}
