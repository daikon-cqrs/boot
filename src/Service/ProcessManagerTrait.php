<?php

declare (strict_types=1);

namespace Oroshi\Core\Service;

use Assert\Assertion;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;
use Daikon\MessageBus\EnvelopeInterface;
use Daikon\MessageBus\MessageInterface;
use Daikon\Metadata\MetadataInterface;

trait ProcessManagerTrait
{
    public function handle(EnvelopeInterface $envelope): void
    {
        $message = $envelope->getMessage();
        Assertion::isInstanceOf($message, MessageInterface::class);
        $shortName = (new \ReflectionClass($message))->getShortName();
        $handlerMethod = 'when'.ucfirst($shortName);
        $handler = [$this, $handlerMethod];
        if (!is_callable($handler)) {
            throw new \RuntimeException("Handler method '$handlerMethod' is not callable in ".static::class);
        }
        call_user_func($handler, $message, $envelope->getMetadata());
    }

    public function then(CommandInterface $command, MetadataInterface $metadata = null): void
    {
        $this->messageBus->publish($command, 'commands', $metadata);
    }
}
