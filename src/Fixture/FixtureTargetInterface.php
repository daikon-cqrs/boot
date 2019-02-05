<?php

declare(strict_types=1);

namespace Oroshi\Core\Fixture;

use Daikon\MessageBus\MessageBusInterface;

interface FixtureTargetInterface
{
    public function getName(): string;

    public function isEnabled(): bool;

    public function getFixtureList(): FixtureList;

    public function import(int $version = null): FixtureList;
}
