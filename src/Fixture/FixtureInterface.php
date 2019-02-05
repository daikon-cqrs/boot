<?php

declare(strict_types=1);

namespace Oroshi\Core\Fixture;

use Daikon\Interop\ToNativeInterface;
use Daikon\MessageBus\MessageBusInterface;

interface FixtureInterface extends ToNativeInterface
{
    const CHAN_COMMANDS = 'commands';

    public function getName(): string;

    public function getVersion(): int;

    public function import(MessageBusInterface $messageBus): void;
}
