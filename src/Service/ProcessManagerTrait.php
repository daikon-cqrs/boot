<?php declare (strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Service;

use Assert\Assertion;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;
use Daikon\Interop\RuntimeException;
use Daikon\MessageBus\EnvelopeInterface;
use Daikon\MessageBus\MessageInterface;
use Daikon\Metadata\MetadataInterface;
use ReflectionClass;

trait ProcessManagerTrait
{
    public function handle(EnvelopeInterface $envelope): void
    {
        $message = $envelope->getMessage();
        Assertion::isInstanceOf($message, MessageInterface::class);
        $shortName = (new ReflectionClass($message))->getShortName();
        $handlerMethod = 'when'.ucfirst($shortName);
        $handler = [$this, $handlerMethod];
        if (!is_callable($handler)) {
            throw new RuntimeException("Handler method '$handlerMethod' is not callable in ".static::class);
        }
        call_user_func($handler, $message, $envelope->getMetadata());
    }

    public function then(CommandInterface $command, MetadataInterface $metadata = null): void
    {
        $this->messageBus->publish($command, 'commands', $metadata);
    }
}
