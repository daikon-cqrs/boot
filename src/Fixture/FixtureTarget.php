<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Fixture;

use Daikon\Interop\Assertion;
use Daikon\MessageBus\MessageBusInterface;

final class FixtureTarget implements FixtureTargetInterface
{
    private string $key;

    private bool $enabled;

    private FixtureLoaderInterface $fixtureLoader;

    private MessageBusInterface $messageBus;

    private ?FixtureList $fixtureList;

    public function __construct(
        string $key,
        bool $enabled,
        FixtureLoaderInterface $fixtureLoader,
        MessageBusInterface $messageBus
    ) {
        $this->key = $key;
        $this->enabled = $enabled;
        $this->fixtureLoader = $fixtureLoader;
        $this->messageBus = $messageBus;
    }

    public function import(FixtureInterface $fixture): bool
    {
        Assertion::true($this->isEnabled(), sprintf("Fixture '%s' is not enabled.", $fixture->getName()));

        $index = $this->getFixtureList()->find($fixture);

        if ($index !== false) {
            $fixture($this->messageBus);
        }

        return $index !== false;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function isEnabled(): bool
    {
        return $this->enabled === true;
    }

    public function getFixtureList(): FixtureList
    {
        if (!isset($this->fixtureList)) {
            //@todo fix fixture class loading to avoid this
            $this->fixtureList = $this->fixtureLoader->load();
        }
        return $this->fixtureList;
    }
}
