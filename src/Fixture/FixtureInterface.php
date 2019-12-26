<?php

declare(strict_types=1);

namespace Oroshi\Core\Fixture;

use Daikon\Interop\ToNativeInterface;
use Daikon\MessageBus\MessageBusInterface;

interface FixtureInterface extends ToNativeInterface
{
    public function getName(): string;

    public function getVersion(): int;

    public function import(MessageBusInterface $messageBus): void;
}
