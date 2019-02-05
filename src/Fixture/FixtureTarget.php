<?php

declare(strict_types=1);

namespace Oroshi\Core\Fixture;

use Assert\Assertion;
use Daikon\MessageBus\MessageBusInterface;

final class FixtureTarget implements FixtureTargetInterface
{
    /** @var string */
    private $name;

    /** @var bool */
    private $enabled;

    /** @var FixtureLoaderInterface */
    private $fixtureLoader;

    /** @var FixtureList|null */
    private $fixtureList;

    public function __construct(
        string $name,
        bool $enabled,
        FixtureLoaderInterface $fixtureLoader
    ) {
        $this->name = $name;
        $this->enabled = $enabled;
        $this->fixtureLoader = $fixtureLoader;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled === true;
    }

    public function getFixtureList(): FixtureList
    {
        if (!isset($this->fixtureList)) {
            $this->fixtureList = $this->fixtureLoader->load();
        }
        return $this->fixtureList;
    }

    public function import(MessageBusInterface $messageBus, int $version = null): FixtureList
    {
        Assertion::true($this->isEnabled());

        $fixtureList = $this->getFixtureList();

        $completedImports = [];
        foreach ($fixtureList as $fixture) {
            if ($fixture->getVersion() < $version) {
                continue;
            }
            $fixture->import($messageBus);
            $completedImports[] = $fixture;
        }

        return new FixtureList($completedImports);
    }
}
