<?php

declare(strict_types=1);

namespace Oroshi\Core\MessageBus;

use Auryn\Injector;
use Daikon\EventSourcing\Aggregate\AggregateAlias;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;
use Daikon\EventSourcing\EventStore\UnitOfWorkMap;
use Daikon\MessageBus\EnvelopeInterface;
use Daikon\MessageBus\Channel\Subscription\MessageHandler\MessageHandlerInterface;

final class CommandRouter implements MessageHandlerInterface
{
    /** @var array */
    private $spawnedHandlers;

    /** @var array */
    private $handlerMap;

    public function __construct(array $handlerMap)
    {
        $this->handlerMap = $handlerMap;
        $this->spawnedHandlers = [];
    }

    public function handle(EnvelopeInterface $envelope): void
    {
        /** @var CommandInterface $command */
        $command = $envelope->getMessage();
        if (!is_a($command, CommandInterface::class)) {
            return;
        }

        $commandFqcn = get_class($command);
        if (!isset($this->handlerMap[$commandFqcn])) {
            throw new \RuntimeException("No handler assigned to given command $commandFqcn");
        }
        if (!isset($this->spawnedHandlers[$commandFqcn])) {
            $this->spawnedHandlers[$commandFqcn] = $this->handlerMap[$commandFqcn]();
        }

        $this->spawnedHandlers[$commandFqcn]->handle($envelope);
    }
}
