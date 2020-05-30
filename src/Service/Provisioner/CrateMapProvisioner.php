<?php declare(strict_types=1);
/**
 * This file is part of the daikon-cqrs/boot project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Daikon\Boot\Service\Provisioner;

use Auryn\Injector;
use Daikon\Boot\Crate\Crate;
use Daikon\Boot\Crate\CrateMap;
use Daikon\Boot\Service\ServiceDefinitionInterface;
use Daikon\Config\ConfigProviderInterface;

final class CrateMapProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $crateConfigs = (array)$configProvider->get('crates', []);
        $factory = function () use ($crateConfigs): CrateMap {
            $crates = [];
            foreach ($crateConfigs as $crateName => $crateConfig) {
                $crates[$crateName] = new Crate($crateConfig);
            }
            return new CrateMap($crates);
        };

        $injector
            ->share(CrateMap::class)
            ->delegate(CrateMap::class, $factory);
    }
}
