<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Fixture;

use Daikon\Boot\Service\Provisioner\MessageBusProvisioner;
use Daikon\EventSourcing\Aggregate\Command\CommandInterface;
use Daikon\MessageBus\MessageBusInterface;
use Daikon\Metadata\MetadataInterface;
use ReflectionClass;

abstract class Fixture implements FixtureInterface
{
    protected MessageBusInterface $messageBus;

    abstract protected function import(): void;

    public function __invoke(MessageBusInterface $messageBus): void
    {
        $this->messageBus = $messageBus;
        $this->import();
    }

    public function getName(): string
    {
        $shortName = (new ReflectionClass(static::class))->getShortName();
        if (!preg_match('/^(?<name>.+?)\d+$/', $shortName, $matches)) {
            throw new FixtureException("Unexpected fixture name in '$shortName'.");
        }
        return $matches['name'];
    }

    public function getVersion(): int
    {
        $shortName = (new ReflectionClass(static::class))->getShortName();
        if (!preg_match('/(?<version>\d{14})$/', $shortName, $matches)) {
            throw new FixtureException("Unexpected fixture version in '$shortName'.");
        }
        return intval($matches['version']);
    }

    public function toNative(): array
    {
        return [
            '@type' => static::class,
            'name' => $this->getName(),
            'version' => $this->getVersion()
        ];
    }

    protected function publish(CommandInterface $command, MetadataInterface $metadata = null): void
    {
        $this->messageBus->publish($command, MessageBusProvisioner::COMMANDS_CHANNEL, $metadata);
    }
}
