<?php

declare(strict_types=1);

namespace Oroshi\Core\MessageBus;

use Assert\Assertion;
use Auryn\Injector;
use Daikon\EventSourcing\Aggregate\AggregateAlias;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;
use Daikon\EventSourcing\EventStore\UnitOfWorkMap;
use Daikon\MessageBus\Channel\Subscription\MessageHandler\MessageHandlerInterface;
use Daikon\MessageBus\EnvelopeInterface;

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

    public function handle(EnvelopeInterface $envelope): bool
    {
        $command = $envelope->getMessage();
        Assertion::implementsInterface($command, CommandInterface::class);
        $commandFqcn = get_class($command);
        if (!isset($this->handlerMap[$commandFqcn])) {
            throw new \RuntimeException("No handler assigned to given command $commandFqcn");
        }
        if (!isset($this->spawnedHandlers[$commandFqcn])) {
            $this->spawnedHandlers[$commandFqcn] = $this->handlerMap[$commandFqcn]();
        }
        return $this->spawnedHandlers[$commandFqcn]->handle($envelope);
    }
}
