<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Fixture;

use Daikon\Interop\ToNativeInterface;
use Daikon\MessageBus\MessageBusInterface;

interface FixtureInterface extends ToNativeInterface
{
    public function getName(): string;

    public function getVersion(): int;

    public function import(MessageBusInterface $messageBus): void;
}
