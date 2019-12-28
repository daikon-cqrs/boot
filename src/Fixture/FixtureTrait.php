<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Fixture;

use ReflectionClass;

trait FixtureTrait
{
    public function getName(): string
    {
        $shortName = (new ReflectionClass(static::class))->getShortName();
        if (!preg_match('#^(?<name>.+?)\d+$#', $shortName, $matches)) {
            throw new FixtureException("Unexpected fixture name in $shortName");
        }
        return $matches['name'];
    }

    public function getVersion(): int
    {
        $shortName= (new ReflectionClass(static::class))->getShortName();
        if (!preg_match('#(?<version>\d{14})$#', $shortName, $matches)) {
            throw new FixtureException("Unexpected fixture version in $shortName");
        }
        return intval($matches['version']);
    }

    public function toNative(): array
    {
        return [
            '@type' => static::class,
            'name' => $this->getName(),
            'version' => $this->getVersion()
        ];
    }
}
