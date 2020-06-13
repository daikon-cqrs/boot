<?php declare (strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Service;

use Daikon\EventSourcing\Aggregate\Command\CommandInterface;
use Daikon\Interop\Assertion;
use Daikon\Interop\RuntimeException;
use Daikon\MessageBus\EnvelopeInterface;
use Daikon\MessageBus\MessageInterface;
use Daikon\Metadata\MetadataInterface;
use ReflectionClass;

trait ProcessManagerTrait
{
    public function handle(EnvelopeInterface $envelope): void
    {
        Assertion::isInstanceOf(
            $message = $envelope->getMessage(),
            MessageInterface::class,
            sprintf("Process manager message '%s' must implement '%s'.", get_class($message), MessageInterface::class)
        );
        $shortName = (new ReflectionClass($message))->getShortName();
        $handlerMethod = 'when'.ucfirst($shortName);
        $handler = [$this, $handlerMethod];
        if (!is_callable($handler)) {
            throw new RuntimeException(
                sprintf("Handler method '%s' is not callable in '%s'.", $handlerMethod, static::class)
            );
        }
        $handler($message, $envelope->getMetadata());
    }

    public function then(CommandInterface $command, MetadataInterface $metadata = null): void
    {
        $this->messageBus->publish($command, 'commands', $metadata);
    }
}
