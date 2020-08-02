<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Fixture;

use Daikon\Interop\ToNativeInterface;
use Daikon\MessageBus\MessageBusInterface;

interface FixtureInterface extends ToNativeInterface
{
    public function __invoke(MessageBusInterface $messageBus): void;

    public function getName(): string;

    public function getVersion(): int;
}
