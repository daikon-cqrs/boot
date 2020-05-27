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
use Daikon\Dbal\Connector\ConnectorMap;
use Daikon\Dbal\Storage\StorageAdapterMap;
use Oroshi\Core\Service\ServiceDefinitionInterface;

final class StorageAdapterMapProvisioner implements ProvisionerInterface
{
    public function provision(
        Injector $injector,
        ConfigProviderInterface $configProvider,
        ServiceDefinitionInterface $serviceDefinition
    ): void {
        $adapterConfigs = (array)$configProvider->get('databases.storage_adapters', []);
        $factory = function (ConnectorMap $connectorMap) use ($injector, $adapterConfigs): StorageAdapterMap {
            $adapters = [];
            foreach ($adapterConfigs as $adapterName => $adapterConfigs) {
                $adapterClass = $adapterConfigs['class'];
                $adapters[$adapterName] = $injector->make(
                    $adapterClass,
                    [
                        ':connector' => $connectorMap->get($adapterConfigs['connector']),
                        ':settings' => $adapterConfigs['settings'] ?? []
                    ]
                );
            }
            return new StorageAdapterMap($adapters);
        };

        $injector
            ->share(StorageAdapterMap::class)
            ->delegate(StorageAdapterMap::class, $factory);
    }
}
