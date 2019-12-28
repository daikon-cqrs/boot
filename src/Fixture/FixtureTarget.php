<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    /** @var MessageBusInterface */
    private $messageBus;

    /** @var FixtureList|null */
    private $fixtureList;

    public function __construct(
        string $name,
        bool $enabled,
        FixtureLoaderInterface $fixtureLoader,
        MessageBusInterface $messageBus
    ) {
        $this->name = $name;
        $this->enabled = $enabled;
        $this->fixtureLoader = $fixtureLoader;
        $this->messageBus = $messageBus;
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
        if (!$this->fixtureList) {
            //@todo fix fixture class loading to avoid this
            $this->fixtureList = $this->fixtureLoader->load();
        }
        return $this->fixtureList;
    }

    public function import(FixtureInterface $fixture): bool
    {
        Assertion::true($this->isEnabled());

        $index = $this->getFixtureList()->indexOf($fixture);
        if ($index !== false) {
            $fixture->import($this->messageBus);
        }

        return $index !== false;
    }
}
