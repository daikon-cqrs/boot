<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Fixture;

interface FixtureTargetInterface
{
    public function import(FixtureInterface $fixture): bool;

    public function getName(): string;

    public function isEnabled(): bool;

    public function getFixtureList(): FixtureList;
}
