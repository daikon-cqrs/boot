<?php declare(strict_types=1);
/**
 * This file is part of the oroshi/oroshi-core project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oroshi\Core\Service\Provisioner;

use Auryn\Injector;
use Daikon\Config\ConfigProviderInterface;
use Oroshi\Core\Crate\Crate;
use Oroshi\Core\Crate\CrateMap;
use Oroshi\Core\Service\ServiceDefinitionInterface;

final class CrateMapProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $crateConfigs = $configProvider->get('crates', []);
        $cratesDir = $configProvider->get('app.crates_dir');
        $factory = function () use ($crateConfigs, $cratesDir): CrateMap {
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
