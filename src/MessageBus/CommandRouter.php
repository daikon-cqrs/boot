<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\MessageBus;

use Assert\Assertion;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;
use Daikon\MessageBus\EnvelopeInterface;
use Daikon\MessageBus\Channel\Subscription\MessageHandler\MessageHandlerInterface;
use Oroshi\Core\Exception\RuntimeException;

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
            throw new RuntimeException("No handler assigned to given command $commandFqcn");
        }
        if (!isset($this->spawnedHandlers[$commandFqcn])) {
            $this->spawnedHandlers[$commandFqcn] = $this->handlerMap[$commandFqcn]();
        }

        $this->spawnedHandlers[$commandFqcn]->handle($envelope);
    }
}
