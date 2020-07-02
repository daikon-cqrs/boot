<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Tests\Boot\MessageBus;

use Daikon\Boot\MessageBus\CommandRouter;
use Daikon\EventSourcing\Aggregate\Command\CommandHandler;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;
use Daikon\Interop\InvalidArgumentException;
use Daikon\Interop\RuntimeException;
use Daikon\MessageBus\Envelope;
use Daikon\MessageBus\MessageInterface;
use PHPUnit\Framework\TestCase;

final class CommandRouterTest extends TestCase
{
    public function testInvalidCommand(): void
    {
        /** @var MessageInterface $messageMock */
        $messageMock = $this->createMock(MessageInterface::class);
        $envelope = Envelope::wrap($messageMock);
        $commandRouter = new CommandRouter;

        $this->expectException(InvalidArgumentException::class);
        $commandRouter->handle($envelope);
    }

    public function testUnhandledCommand(): void
    {
        /** @var MessageInterface $messageMock */
        $messageMock = $this->createMock(CommandInterface::class);
        $envelope = Envelope::wrap($messageMock);
        $commandRouter = new CommandRouter;

        $this->expectException(RuntimeException::class);
        $commandRouter->handle($envelope);
    }

    public function testHandledCommand(): void
    {
        /** @var MessageInterface $messageMock */
        $messageMock = $this->createMock(CommandInterface::class);
        $envelope = Envelope::wrap($messageMock);
        $handlerMock = $this->createMock(CommandHandler::class);
        $handlerMock->expects($this->once())->method('handle')->with($envelope);
        /** @var CommandHandler $handlerMock */
        $handler = fn(): CommandHandler => $handlerMock;
        $commandRouter = new CommandRouter([get_class($messageMock) => $handler]);

        $commandRouter->handle($envelope);
    }
}
