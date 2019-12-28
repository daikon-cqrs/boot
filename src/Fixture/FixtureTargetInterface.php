<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Fixture;

interface FixtureTargetInterface
{
    public function getName(): string;

    public function isEnabled(): bool;

    public function getFixtureList(): FixtureList;

    public function import(FixtureInterface $fixture): bool;
}
