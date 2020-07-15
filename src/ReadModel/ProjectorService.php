<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\ReadModel;

use Daikon\Boot\Service\Provisioner\MessageBusProvisioner;
use Daikon\EventSourcing\EventStore\Commit\CommitInterface;
use Daikon\Interop\Assertion;
use Daikon\MessageBus\EnvelopeInterface;
use Daikon\MessageBus\MessageBusInterface;
use Daikon\ReadModel\Projector\EventProjectorMap;
use Daikon\ReadModel\Projector\ProjectorInterface;
use Daikon\ReadModel\Projector\ProjectorServiceInterface;

final class ProjectorService implements ProjectorServiceInterface
{
    private EventProjectorMap $eventProjectorMap;

    private MessageBusInterface $messageBus;

    public function __construct(EventProjectorMap $eventProjectorMap, MessageBusInterface $messageBus)
    {
        $this->eventProjectorMap = $eventProjectorMap;
        $this->messageBus = $messageBus;
    }

    public function handle(EnvelopeInterface $envelope): void
    {
        /** @var CommitInterface $commit */
        $commit = $envelope->getMessage();
        Assertion::implementsInterface($commit, CommitInterface::class);

        $metadata = $envelope->getMetadata();
        foreach ($commit->getEventLog() as $event) {
            $projectors = $this->eventProjectorMap->findFor($event);
            $projectors->map(fn(string $key, ProjectorInterface $projector) => $projector->handle($envelope));
            $this->messageBus->publish($event, MessageBusProvisioner::EVENTS_CHANNEL, $metadata);
        }
    }
}
