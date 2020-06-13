<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\MessageBus;

use Daikon\EventSourcing\Aggregate\Command\CommandInterface;
use Daikon\Interop\Assertion;
use Daikon\Interop\RuntimeException;
use Daikon\MessageBus\EnvelopeInterface;
use Daikon\MessageBus\Channel\Subscription\MessageHandler\MessageHandlerInterface;

final class CommandRouter implements MessageHandlerInterface
{
    private array $spawnedHandlers;

    private array $handlerMap;

    public function __construct(array $handlerMap)
    {
        $this->handlerMap = $handlerMap;
        $this->spawnedHandlers = [];
    }

    public function handle(EnvelopeInterface $envelope): void
    {
        /** @var CommandInterface $command */
        $command = $envelope->getMessage();
        Assertion::implementsInterface($command, CommandInterface::class);

        $commandFqcn = get_class($command);
        if (!isset($this->handlerMap[$commandFqcn])) {
            throw new RuntimeException("No handler assigned to given command '$commandFqcn'.");
        }
        if (!isset($this->spawnedHandlers[$commandFqcn])) {
            $this->spawnedHandlers[$commandFqcn] = $this->handlerMap[$commandFqcn]();
        }

        $this->spawnedHandlers[$commandFqcn]->handle($envelope);
    }
}
